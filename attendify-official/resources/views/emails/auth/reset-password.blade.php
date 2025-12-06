@component('mail::message')
# Reset your password

Click the button below to reset your password.

@component('mail::button', ['url' => $link])
Reset Password
@endcomponent

If you didn’t request this, you can ignore this email.

Thanks,  
**Attendify**
@endcomponent
