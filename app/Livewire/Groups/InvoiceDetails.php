<?php

namespace App\Livewire\Groups;

use App\Models\Group;
use App\Models\PaymentRequest;
use App\Services\Square\SquareApiClient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;

class InvoiceDetails extends Component
{
    use AuthorizesRequests;

    public Group $group;

    public string $activeTab = 'all';

    public function mount(Group $group): void
    {
        $this->authorize('view', $group);

        $this->group = $group->load([
            'cycles.paymentRequests' => function ($query) {
                $query->with('member.user')->orderBy('created_at', 'desc');
            },
        ]);
    }

    #[Computed]
    public function allPaymentRequests()
    {
        $requests = [];

        foreach ($this->group->cycles as $cycle) {
            foreach ($cycle->paymentRequests as $pr) {
                $requests[] = $pr;
            }
        }

        return collect($requests)->sortByDesc('created_at');
    }

    #[Computed]
    public function paidRequests()
    {
        return $this->allPaymentRequests->filter(fn ($pr) => $pr->status === 'paid');
    }

    #[Computed]
    public function pendingRequests()
    {
        return $this->allPaymentRequests->filter(fn ($pr) => $pr->status === 'pending');
    }

    #[Computed]
    public function failedRequests()
    {
        return $this->allPaymentRequests->filter(fn ($pr) => $pr->status === 'failed');
    }

    #[Computed]
    public function filteredRequests()
    {
        return match ($this->activeTab) {
            'paid' => $this->paidRequests,
            'pending' => $this->pendingRequests,
            'failed' => $this->failedRequests,
            default => $this->allPaymentRequests,
        };
    }

    /**
     * Download Square invoice as PDF
     */
    public function downloadSquareInvoice(int $paymentRequestId)
    {
        try {
            $pr = PaymentRequest::findOrFail($paymentRequestId);
            $this->authorize('view', $pr->group);

            if (! $pr->square_invoice_id) {
                $this->dispatch('error', 'No invoice available for this payment request.');

                return;
            }

            $owner = $pr->group?->owner;

            if (! $owner || empty($owner->square_access_token)) {
                $this->dispatch('error', 'Owner has not connected a Square account.');

                return;
            }

            $squareClient = new SquareApiClient($owner->square_access_token);
            $invoice = $squareClient->getInvoice($pr->square_invoice_id);

            if (! $invoice || ! isset($invoice['invoice']['id'])) {
                $this->dispatch('error', 'Could not retrieve invoice from Square.');

                return;
            }

            $invoiceData = $invoice['invoice'];

            return $this->generateInvoicePdf($invoiceData, $pr);
        } catch (\Exception $e) {
            Log::error('Failed to download Square invoice', [
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('error', 'Failed to download invoice: '.$e->getMessage());
        }
    }

    /**
     * Download paid receipt
     */
    public function downloadReceipt(int $paymentRequestId)
    {
        try {
            $pr = PaymentRequest::findOrFail($paymentRequestId);
            $this->authorize('view', $pr->group);

            if ($pr->status !== 'paid' || ! $pr->paid_at) {
                $this->dispatch('error', 'This payment has not been completed yet.');

                return;
            }

            return $this->generateReceiptPdf($pr);
        } catch (\Exception $e) {
            Log::error('Failed to generate receipt', [
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('error', 'Failed to generate receipt: '.$e->getMessage());
        }
    }

    /**
     * Generate invoice PDF using DomPDF
     */
    private function generateInvoicePdf(array $invoiceData, PaymentRequest $pr)
    {
        $html = view('exports.square-invoice', [
            'invoice' => $invoiceData,
            'paymentRequest' => $pr,
            'group' => $pr->group,
            'member' => $pr->member,
        ])->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4')
            ->setOption('isPhpEnabled', true);

        return $pdf->download("invoice-{$pr->pr_code}.pdf");
    }

    /**
     * Generate receipt PDF using DomPDF
     */
    private function generateReceiptPdf(PaymentRequest $pr)
    {
        $html = view('exports.payment-receipt', [
            'paymentRequest' => $pr,
            'group' => $pr->group,
            'member' => $pr->member,
        ])->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4')
            ->setOption('isPhpEnabled', true);

        return $pdf->download("receipt-{$pr->pr_code}.pdf");
    }

    public function render()
    {
        return view('livewire.groups.invoice-details', [
            'allRequests' => $this->allPaymentRequests,
            'paidRequests' => $this->paidRequests,
            'pendingRequests' => $this->pendingRequests,
            'failedRequests' => $this->failedRequests,
            'filteredRequests' => $this->filteredRequests,
        ]);
    }
}
