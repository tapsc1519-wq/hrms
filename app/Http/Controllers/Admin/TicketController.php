<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = Ticket::where('organization_id', $this->orgId())
            ->with(['requester', 'assignedTo'])
            ->latest();

        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('category')) $query->where('category', $request->category);
        if ($request->filled('priority')) $query->where('priority', $request->priority);
        if ($request->filled('search')) {
            $query->where(fn($q) =>
                $q->where('subject', 'like', '%' . $request->search . '%')
                  ->orWhere('ticket_number', 'like', '%' . $request->search . '%')
            );
        }

        $tickets = $query->paginate(20)->withQueryString();

        $counts = [
            'open'        => Ticket::where('organization_id', $this->orgId())->where('status', 'open')->count(),
            'in_progress' => Ticket::where('organization_id', $this->orgId())->where('status', 'in_progress')->count(),
            'resolved'    => Ticket::where('organization_id', $this->orgId())->where('status', 'resolved')->count(),
        ];

        return view('admin.tickets.index', compact('tickets', 'counts'));
    }

    public function show(Ticket $ticket)
    {
        abort_if($ticket->organization_id !== $this->orgId(), 403);

        $ticket->load(['requester', 'assignedTo', 'asset', 'attachments']);
        $replies    = $ticket->replies()->with(['user', 'attachments'])->get();
        $adminUsers = User::where('organization_id', $this->orgId())
            ->where('role', 'admin')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.tickets.show', compact('ticket', 'replies', 'adminUsers'));
    }

    public function reply(Request $request, Ticket $ticket)
    {
        abort_if($ticket->organization_id !== $this->orgId(), 403);

        $request->validate([
            'message'       => 'required|string|max:5000',
            'is_internal'   => 'boolean',
            'attachments'   => 'nullable|array|max:5',
            'attachments.*' => 'file|max:5120|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,zip',
        ]);

        $reply = TicketReply::create([
            'ticket_id'   => $ticket->id,
            'user_id'     => auth()->id(),
            'message'     => $request->message,
            'is_internal' => $request->boolean('is_internal'),
        ]);

        $this->storeAttachments($request, $ticket->id, $reply->id);

        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }

        return back()->with('success', 'Reply posted.');
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        abort_if($ticket->organization_id !== $this->orgId(), 403);

        $request->validate(['status' => 'required|in:open,in_progress,resolved,closed']);

        $data = ['status' => $request->status];
        if ($request->status === 'resolved') $data['resolved_at'] = now();
        if ($request->status === 'closed')   $data['closed_at']   = now();

        $ticket->update($data);
        return back()->with('success', 'Ticket status updated.');
    }

    public function assign(Request $request, Ticket $ticket)
    {
        abort_if($ticket->organization_id !== $this->orgId(), 403);

        $request->validate(['assigned_to' => 'nullable|exists:users,id']);
        $ticket->update(['assigned_to' => $request->assigned_to ?: null]);
        return back()->with('success', 'Ticket assigned.');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function storeAttachments(Request $request, int $ticketId, ?int $replyId): void
    {
        if (! $request->hasFile('attachments')) return;

        foreach ($request->file('attachments') as $file) {
            if (! $file->isValid()) continue;

            $path = $file->store("ticket-attachments/{$ticketId}", 'public');

            TicketAttachment::create([
                'ticket_id'     => $ticketId,
                'reply_id'      => $replyId,
                'user_id'       => auth()->id(),
                'original_name' => $file->getClientOriginalName(),
                'file_path'     => $path,
                'mime_type'     => $file->getMimeType(),
                'file_size'     => $file->getSize(),
            ]);
        }
    }
}
