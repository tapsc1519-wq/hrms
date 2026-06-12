<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::where('requester_id', auth()->id())
            ->with(['assignedTo', 'asset'])
            ->latest()
            ->paginate(20);

        return view('staff.tickets.index', compact('tickets'));
    }

    public function create()
    {
        $orgId  = auth()->user()->organization_id;
        $assets = Asset::where('organization_id', $orgId)
            ->orderBy('name')
            ->get(['id', 'name', 'asset_tag']);

        return view('staff.tickets.create', compact('assets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category'        => 'required|in:fixed_assets,itam_support,other_support',
            'subject'         => 'required|string|max:255',
            'description'     => 'required|string|max:5000',
            'priority'        => 'required|in:low,normal,high,urgent',
            'asset_id'        => 'required_if:category,fixed_assets|nullable|exists:assets,id',
            'attachments'     => 'nullable|array|max:5',
            'attachments.*'   => 'file|max:5120|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,zip',
        ]);

        $orgId = auth()->user()->organization_id;
        $count = Ticket::where('organization_id', $orgId)->count();

        $ticket = Ticket::create([
            'category'        => $validated['category'],
            'subject'         => $validated['subject'],
            'description'     => $validated['description'],
            'priority'        => $validated['priority'],
            'asset_id'        => $validated['asset_id'] ?? null,
            'organization_id' => $orgId,
            'requester_id'    => auth()->id(),
            'ticket_number'   => 'TKT-' . date('Y') . '-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT),
            'status'          => 'open',
        ]);

        $this->storeAttachments($request, $ticket->id, null);

        return redirect()->route('staff.tickets.index')
            ->with('success', 'Ticket submitted successfully. We will get back to you soon.');
    }

    public function show(Ticket $ticket)
    {
        abort_if($ticket->requester_id !== auth()->id(), 403);

        $ticket->load(['attachments', 'asset', 'assignedTo']);
        $replies = $ticket->publicReplies()->with(['user', 'attachments'])->get();

        return view('staff.tickets.show', compact('ticket', 'replies'));
    }

    public function reply(Request $request, Ticket $ticket)
    {
        abort_if($ticket->requester_id !== auth()->id(), 403);
        abort_if(! $ticket->is_open, 403, 'This ticket is closed.');

        $request->validate([
            'message'       => 'required|string|max:5000',
            'attachments'   => 'nullable|array|max:5',
            'attachments.*' => 'file|max:5120|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,zip',
        ]);

        $reply = TicketReply::create([
            'ticket_id'   => $ticket->id,
            'user_id'     => auth()->id(),
            'message'     => $request->message,
            'is_internal' => false,
        ]);

        $this->storeAttachments($request, $ticket->id, $reply->id);

        if ($ticket->status === 'resolved') {
            $ticket->update(['status' => 'open', 'resolved_at' => null]);
        }

        return back()->with('success', 'Reply added.');
    }

    public function close(Ticket $ticket)
    {
        abort_if($ticket->requester_id !== auth()->id(), 403);
        abort_if(! $ticket->is_open, 403);

        $ticket->update(['status' => 'closed', 'closed_at' => now()]);
        return back()->with('success', 'Ticket closed.');
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
