@component('mail::message')
# Verify your email

Please confirm your email address to complete your registration.

@component('mail::button', ['url' => $link])
Verify Email
@endcomponent

If you didn’t create an account, you can ignore this email.

Thanks,  
**Attendify**
@endcomponent
