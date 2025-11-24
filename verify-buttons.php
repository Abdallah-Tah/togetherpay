<?php

use App\Models\User;
use App\Models\Group;
use App\Mail\MemberInvitationMail;
use Illuminate\Container\Container;
use Illuminate\Mail\Markdown;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Mock data
$user = new User(['name' => 'Test User', 'email' => 'test@example.com']);
$group = new Group(['name' => 'Test Group']);
$token = 'test-token';

// Render MemberInvitationMail
$mail = new MemberInvitationMail($group, $user, $token);
$markdown = Container::getInstance()->make(Markdown::class);
$html = $mail->render();

file_put_contents('storage/logs/member-invitation-buttons.html', $html);
echo "Rendered MemberInvitationMail to storage/logs/member-invitation-buttons.html\n";
