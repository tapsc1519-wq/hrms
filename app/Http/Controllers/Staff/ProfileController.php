<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AssetAssignment;
use App\Models\EmployeeDocument;
use App\Models\EmployeeDocumentRequest;
use App\Models\EmployeeProfile;
use App\Models\SoftwareAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show()
    {
        $employee = EmployeeProfile::where('user_id', auth()->id())
            ->with([
                'user.department',
                'manager',
                'facility',
                'location',
                'shift',
                'documents.uploader',
                'documentRequests.requester',
                'documentRequests.reviewer',
                'documentRequests.fulfilledDocument',
            ])
            ->first();

        if (!$employee) {
            return view('staff.profile.missing');
        }

        $assetAssignments = AssetAssignment::where('user_id', auth()->id())
            ->where('status', 'active')
            ->with(['asset.category', 'asset.location'])
            ->latest('assigned_date')
            ->get();

        $softwareAssignments = SoftwareAssignment::where('user_id', auth()->id())
            ->where('status', 'active')
            ->with(['license.software'])
            ->latest('assigned_date')
            ->get();

        return view('staff.profile.show', compact('employee', 'assetAssignments', 'softwareAssignments'));
    }

    public function uploadRequestedDocument(Request $request, EmployeeDocumentRequest $documentRequest)
    {
        $employee = EmployeeProfile::where('user_id', auth()->id())->firstOrFail();
        abort_if($documentRequest->employee_profile_id !== $employee->id, 403);
        abort_if(!in_array($documentRequest->status, ['pending', 'rejected'], true), 422, 'This document request is not open for upload.');

        $data = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,txt,zip'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'expiry_date' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($request, $data, $employee, $documentRequest) {
            $file = $request->file('file');
            $path = $file->store("employee-documents/{$employee->id}", 'public');

            $document = EmployeeDocument::create([
                'organization_id' => $employee->organization_id,
                'employee_profile_id' => $employee->id,
                'uploaded_by' => auth()->id(),
                'document_type' => $documentRequest->document_type,
                'title' => $documentRequest->title,
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'expiry_date' => $data['expiry_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $documentRequest->update([
                'fulfilled_document_id' => $document->id,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);
        });

        return back()->with('success', 'Document uploaded successfully. HR/Admin can now review it.');
    }
}
