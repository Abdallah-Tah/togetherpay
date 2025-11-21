@component('emails.layout', ['title' => "You're on the list!"])
    <h1>You're on the list! 🚀</h1>
    
    <p>Thanks for signing up! We're thrilled you're interested in TogetherPay.</p>
    
    <p>We're working hard to put the finishing touches on our mobile app. You'll be the first to know as soon as it's available for download on iOS and Android.</p>
    
    <p>In the meantime, get your group ready to split bills without the awkwardness.</p>
    
    <div class="button-container">
        <a href="{{ route('welcome') }}" class="button">Visit Website</a>
    </div>
@endcomponent
