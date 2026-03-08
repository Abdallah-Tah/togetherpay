@component('emails.layout', ['title' => "You were added to {$group->name}"])
    <h1>You were added to {{ $group->name }}</h1>

    <p>Hi {{ $notifiable->name }},</p>

    <p>{{ $addedBy->name }} added you to the group <strong>{{ $group->name }}</strong>.</p>

    <p>No account setup is required. Your existing TogetherPay account is ready to use.</p>

    <div class="button-container">
        <a href="{{ $appUrl }}" class="button">Open TogetherPay</a>
    </div>

    <p style="font-size: 14px; color: #71717a; margin-top: 24px;">
        You will receive new requests from this group when charges are created.
    </p>
@endcomponent
