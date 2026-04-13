<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Forms;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FormSubmissionController extends Controller
{
    public function index(Form $form): View
    {
        $submissions = $form->submissions()
            ->with('lead:id,name,phone,email')
            ->orderByDesc('submitted_at')
            ->paginate(25);

        return view('tenant.forms.submissions', compact('form', 'submissions'));
    }

    public function export(Form $form): BinaryFileResponse
    {
        $submissions = $form->submissions()
            ->with('lead:id,name,phone,email')
            ->orderByDesc('submitted_at')
            ->get();

        $fields = $form->fields ?? [];
        $headers = array_merge(
            ['#', 'Lead', 'Data'],
            array_map(fn ($f) => $f['label'] ?? $f['id'], $fields),
            ['IP']
        );

        $rows = $submissions->map(function (FormSubmission $sub) use ($fields) {
            $row = [
                $sub->id,
                $sub->lead?->name ?? '-',
                $sub->submitted_at?->format('d/m/Y H:i'),
            ];

            foreach ($fields as $field) {
                $row[] = $sub->data[$field['id']] ?? '-';
            }

            $row[] = $sub->ip_address;
            return $row;
        });

        $filename = "submissoes-{$form->slug}-" . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
