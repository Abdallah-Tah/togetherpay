@component('emails.layout', ['title' => "You're on the list!"])
    <h1>You're on the list</h1>

    <p>Thanks for signing up for TogetherPay updates.</p>

    <p>We will email you as soon as the app is available for download on iOS and Android.</p>

    <p>Until then, you can visit our site for product updates.</p>

    <div class="button-container">
        <a href="{{ route('welcome') }}" class="button">Visit Website</a>
    </div>
@endcomponent
