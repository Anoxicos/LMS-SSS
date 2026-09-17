<?php

namespace App\Services;

use App\Models\CompanyRequest;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\Auth\XOAuth2Authenticator;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Vinkla\Hashids\Facades\Hashids;

use App\Models\IndividualRequest;

class MicrosoftOAuthMailer
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Code
    |--------------------------------------------------------------------------
    */
    public function sendVerificationCode(
        User $user,
        string $code
    ): void {
        $accessToken = $this->getAccessToken();

        $username = config('services.microsoft_mail.username');
        $from = config('services.microsoft_mail.from');
        $fromName = config(
            'services.microsoft_mail.from_name',
            'SafeSky LMS'
        );

        if (!$username || !$from) {
            throw new RuntimeException(
                'Microsoft mail configuration is incomplete.'
            );
        }

        $transport = new EsmtpTransport(
            'smtp.office365.com',
            587
        );

        $transport->setUsername($username);
        $transport->setPassword($accessToken);
        $transport->setAuthenticators([
            new XOAuth2Authenticator(),
        ]);

        $html = view(
            'emails.auth.verify-email-code',
            [
                'user' => $user,
                'code' => $code,
                'expirationMinutes' => 10,
            ]
        )->render();

        $text = implode("\n", [
            'SafeSky LMS',
            '',
            "Your email verification code is: {$code}",
            '',
            'This code is valid for 10 minutes.',
            'If you did not request this code, you can ignore this email.',
        ]);

        $email = (new Email())
            ->from(
                new Address(
                    $from,
                    $fromName
                )
            )
            ->to(
                new Address(
                    $user->email,
                    $user->full_name ?: $user->email
                )
            )
            ->subject('Verify your email - SafeSky LMS')
            ->text($text)
            ->html($html);

        $mailer = new Mailer($transport);
        $mailer->send($email);
    }

    /*
    |--------------------------------------------------------------------------
    | Password Reset
    |--------------------------------------------------------------------------
    */
    public function sendPasswordResetLink(
        User $user,
        string $token
    ): void {
        $accessToken = $this->getAccessToken();

        $username = config(
            'services.microsoft_mail.username'
        );

        $from = config(
            'services.microsoft_mail.from'
        );

        $fromName = config(
            'services.microsoft_mail.from_name',
            'SafeSky LMS'
        );

        if (!$username || !$from) {
            throw new RuntimeException(
                'Microsoft mail configuration is incomplete.'
            );
        }

        $resetUrl = route(
            'password.reset',
            [
                'token' => $token,
                'email' => $user->email,
            ]
        );

        $transport = new EsmtpTransport(
            'smtp.office365.com',
            587
        );

        $transport->setUsername($username);
        $transport->setPassword($accessToken);
        $transport->setAuthenticators([
            new XOAuth2Authenticator(),
        ]);

        $expirationMinutes = config(
            'auth.passwords.users.expire',
            60
        );

        $html = view(
            'emails.auth.reset-password',
            [
                'user' => $user,
                'resetUrl' => $resetUrl,
                'expirationMinutes' => $expirationMinutes,
            ]
        )->render();

        $text = implode("\n", [
            'SafeSky LMS',
            '',
            'Password Reset Request',
            '',
            'Hello ' . ($user->full_name ?: $user->email) . ',',
            '',
            'We received a request to reset your password.',
            '',
            'Reset your password:',
            $resetUrl,
            '',
            'This password reset link will expire in '
                . $expirationMinutes
                . ' minutes.',
            '',
            'If you did not request a password reset, you can ignore this email.',
        ]);

        $email = (new Email())
            ->from(
                new Address(
                    $from,
                    $fromName
                )
            )
            ->to(
                new Address(
                    $user->email,
                    $user->full_name ?: $user->email
                )
            )
            ->subject('Reset Your Password - SafeSky LMS')
            ->text($text)
            ->html($html);

        $mailer = new Mailer($transport);
        $mailer->send($email);
    }

    /*
    |--------------------------------------------------------------------------
    | Company Request - Created
    |--------------------------------------------------------------------------
    */
    public function sendCompanyRequestCreated(
        CompanyRequest $companyRequest,
        string $recipientEmail
    ): void {
        $companyRequest->loadMissing([
            'company',
            'course.category',
            'requestedBy',
            'users',
            'accreditations',
        ]);

        $url = route(
            'company-requests.show',
            [
                'hashid' => Hashids::encode(
                    $companyRequest->id
                ),
            ]
        );

        $html = view(
            'emails.company-requests.created',
            [
                'companyRequest' => $companyRequest,
                'url' => $url,
            ]
        )->render();

        $text = implode("\n", [
            'SafeSky LMS',
            '',
            'New Company Training Request',
            '',
            'Reference: ' . $companyRequest->reference,
            'Company: ' . ($companyRequest->company?->name ?? '-'),
            'Course: ' . ($companyRequest->course?->title ?? '-'),
            'Requested by: ' . ($companyRequest->requestedBy?->full_name ?? '-'),
            'Learners: ' . $companyRequest->users->count(),
            '',
            'View request:',
            $url,
        ]);

        $this->sendEmail(
            $recipientEmail,
            'New Company Request - ' . $companyRequest->reference,
            $html,
            $text
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Company Request - Updated
    |--------------------------------------------------------------------------
    */
    public function sendCompanyRequestUpdated(
        CompanyRequest $companyRequest,
        string $recipientEmail
    ): void {
        $companyRequest->loadMissing([
            'company',
            'course.category',
            'requestedBy',
            'users',
            'accreditations',
        ]);

        $url = route(
            'company-requests.show',
            [
                'hashid' => Hashids::encode(
                    $companyRequest->id
                ),
            ]
        );

        $html = view(
            'emails.company-requests.updated',
            [
                'companyRequest' => $companyRequest,
                'url' => $url,
            ]
        )->render();

        $text = implode("\n", [
            'SafeSky LMS',
            '',
            'Company Training Request Updated',
            '',
            'Reference: ' . $companyRequest->reference,
            'Company: ' . ($companyRequest->company?->name ?? '-'),
            'Course: ' . ($companyRequest->course?->title ?? '-'),
            'Updated by: ' . (auth()->user()?->full_name ?? '-'),
            'Learners: ' . $companyRequest->users->count(),
            '',
            'View request:',
            $url,
        ]);

        $this->sendEmail(
            $recipientEmail,
            'Company Request Updated - ' . $companyRequest->reference,
            $html,
            $text
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Company Request - Approved
    |--------------------------------------------------------------------------
    */
    public function sendCompanyRequestApproved(
        CompanyRequest $companyRequest,
        string $recipientEmail
    ): void {
        $companyRequest->loadMissing([
            'company',
            'course.category',
            'requestedBy',
            'validatedBy',
            'users',
            'accreditations',
        ]);

        $url = route(
            'company-requests.show',
            [
                'hashid' => Hashids::encode(
                    $companyRequest->id
                ),
            ]
        );

        $html = view(
            'emails.company-requests.approved',
            [
                'companyRequest' => $companyRequest,
                'url' => $url,
            ]
        )->render();

        $text = implode("\n", [
            'SafeSky LMS',
            '',
            'Your training request has been approved.',
            '',
            'Reference: ' . $companyRequest->reference,
            'Course: ' . ($companyRequest->course?->title ?? '-'),
            'Learners: ' . $companyRequest->users->count(),
            'Status: Approved',
            '',
            'The assigned learners have been notified by email.',
            '',
            'View request:',
            $url,
        ]);

        $this->sendEmail(
            $recipientEmail,
            'Company Request Approved - ' . $companyRequest->reference,
            $html,
            $text
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Company Request - Rejected
    |--------------------------------------------------------------------------
    */
    public function sendCompanyRequestRejected(
        CompanyRequest $companyRequest,
        string $recipientEmail
    ): void {
        $companyRequest->loadMissing([
            'company',
            'course.category',
            'requestedBy',
            'validatedBy',
            'users',
            'accreditations',
        ]);

        $url = route(
            'company-requests.show',
            [
                'hashid' => Hashids::encode(
                    $companyRequest->id
                ),
            ]
        );

        $html = view(
            'emails.company-requests.rejected',
            [
                'companyRequest' => $companyRequest,
                'url' => $url,
            ]
        )->render();

        $text = implode("\n", [
            'SafeSky LMS',
            '',
            'Your training request has been rejected.',
            '',
            'Reference: ' . $companyRequest->reference,
            'Course: ' . ($companyRequest->course?->title ?? '-'),
            'Status: Rejected',
            '',
            'Notes: ' . ($companyRequest->notes ?: '-'),
            '',
            'View request:',
            $url,
        ]);

        $this->sendEmail(
            $recipientEmail,
            'Company Request Rejected - ' . $companyRequest->reference,
            $html,
            $text
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Learner - Course Assigned
    |--------------------------------------------------------------------------
    */
    public function sendLearnerCourseAssigned(
        User $learner,
        CompanyRequest $companyRequest
    ): void {
        $companyRequest->loadMissing([
            'company',
            'course.category',
            'accreditations',
        ]);

        $courseUrl = route('login');

        $html = view(
            'emails.learners.course-assigned',
            [
                'learner' => $learner,
                'companyRequest' => $companyRequest,
                'courseUrl' => $courseUrl,
            ]
        )->render();

        $learnerName = $learner->full_name ?: $learner->email;

        $text = implode("\n", [
            'SafeSky LMS',
            '',
            'Hello ' . $learnerName . ',',
            '',
            'A new course has been assigned to you.',
            '',
            'Course: ' . ($companyRequest->course?->title ?? '-'),
            'Company: ' . ($companyRequest->company?->name ?? '-'),
            'Start date: ' . ($companyRequest->start_date ?: '-'),
            'End date: ' . ($companyRequest->end_date ?: '-'),
            '',
            'Sign in to SafeSky LMS to start your course:',
            $courseUrl,
        ]);

        $this->sendEmail(
            $learner->email,
            'Your Course Is Ready - ' . ($companyRequest->course?->title ?? 'SafeSky LMS'),
            $html,
            $text
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Generic Microsoft OAuth Email Sender
    |--------------------------------------------------------------------------
    */
    private function sendEmail(
        string $recipientEmail,
        string $subject,
        string $html,
        string $text,
        array $attachments = []
    ): void {
        if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException(
                'The recipient email address is invalid.'
            );
        }

        $accessToken = $this->getAccessToken();

        $username = config(
            'services.microsoft_mail.username'
        );

        $from = config(
            'services.microsoft_mail.from'
        );

        $fromName = config(
            'services.microsoft_mail.from_name',
            'SafeSky LMS'
        );

        if (!$username) {
            throw new RuntimeException(
                'MICROSOFT_MAIL_USERNAME is not configured.'
            );
        }

        if (!$from) {
            throw new RuntimeException(
                'MICROSOFT_MAIL_FROM is not configured.'
            );
        }

        $transport = new EsmtpTransport(
            'smtp.office365.com',
            587
        );

        $transport->setUsername($username);
        $transport->setPassword($accessToken);
        $transport->setAuthenticators([
            new XOAuth2Authenticator(),
        ]);

        $email = (new Email())
            ->from(
                new Address(
                    $from,
                    $fromName
                )
            )
            ->to(
                new Address(
                    $recipientEmail
                )
            )
            ->subject($subject)
            ->text($text)
            ->html($html);

        /*
        |--------------------------------------------------------------------------
        | Attachments
        |--------------------------------------------------------------------------
        |
        | Each attachment must contain:
        | - content: raw file content
        | - name: attachment filename
        | - content_type: MIME type (optional)
        |
        */
        foreach ($attachments as $attachment) {
            $content = $attachment['content'] ?? null;
            $name = $attachment['name'] ?? null;
            $contentType = $attachment['content_type'] ?? 'application/pdf';

            if ($content === null || !$name) {
                continue;
            }

            $email->attach(
                $content,
                $name,
                $contentType
            );
        }

        $mailer = new Mailer($transport);
        $mailer->send($email);
    }

    /*
    |--------------------------------------------------------------------------
    | Microsoft OAuth Access Token
    |--------------------------------------------------------------------------
    */
    private function getAccessToken(): string
    {
        $cacheKey = 'microsoft_smtp_oauth_access_token';

        $cachedToken = Cache::get($cacheKey);

        if ($cachedToken) {
            return $cachedToken;
        }

        $tenantId = config(
            'services.microsoft_mail.tenant_id'
        );

        $clientId = config(
            'services.microsoft_mail.client_id'
        );

        $clientSecret = config(
            'services.microsoft_mail.client_secret'
        );

        if (!$tenantId) {
            throw new RuntimeException(
                'MICROSOFT_TENANT_ID is not configured.'
            );
        }

        if (!$clientId) {
            throw new RuntimeException(
                'MICROSOFT_CLIENT_ID is not configured.'
            );
        }

        if (!$clientSecret) {
            throw new RuntimeException(
                'MICROSOFT_CLIENT_SECRET is not configured.'
            );
        }

        $response = Http::asForm()
            ->timeout(20)
            ->post(
                "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
                [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'scope' => 'https://outlook.office365.com/.default',
                    'grant_type' => 'client_credentials',
                ]
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'Microsoft OAuth token error: '
                . $response->body()
            );
        }

        $accessToken = $response->json(
            'access_token'
        );

        if (!$accessToken) {
            throw new RuntimeException(
                'Microsoft did not return an OAuth access token.'
            );
        }

        $expiresIn = (int) $response->json(
            'expires_in',
            3600
        );

        $cacheSeconds = max(
            60,
            $expiresIn - 300
        );

        Cache::put(
            $cacheKey,
            $accessToken,
            now()->addSeconds($cacheSeconds)
        );

        return $accessToken;
    }



    /*
    |--------------------------------------------------------------------------
    | Learner - Welcome / Login Credentials
    |--------------------------------------------------------------------------
    */

    public function sendLearnerWelcomeCredentials(
        User $learner,
        string $temporaryPassword
    ): void 
    {
        $learner->loadMissing('company');

        $loginUrl = route('login');

        $html = view(
            'emails.learners.welcome-credentials',
            [
                'learner' => $learner,
                'loginUrl' => $loginUrl,
                'temporaryPassword' => $temporaryPassword,
            ]
        )->render();

        $learnerName = $learner->full_name ?: $learner->email;

        $text = implode("\n", [
            'SafeSky LMS',
            '',
            'Welcome to SafeSky LMS',
            '',
            'Hello ' . $learnerName . ',',
            '',
            'Your SafeSky LMS learner account has been created.',
            '',
            'Login information:',
            'Login / Email: ' . $learner->email,
            'Temporary password: ' . $temporaryPassword,
            '',
            'Login page:',
            $loginUrl,
            '',
            'IMPORTANT SECURITY NOTICE:',
            'Please change your password after your first login.',
            'Do not share your login credentials with anyone.',
        ]);

        $this->sendEmail(
            $learner->email,
            'Welcome to SafeSky LMS - Your Login Information',
            $html,
            $text
        );
    }

     /*
    |--------------------------------------------------------------------------
    | Learner - Password Updated
    |--------------------------------------------------------------------------
    */
    public function sendLearnerPasswordUpdated(User $learner, string $newTemporaryPassword): void 
    {
        $learner->loadMissing('company');

        $loginUrl = route('login');

        $html = view(
            'emails.learners.password-updated',
            [
                'learner' => $learner,
                'loginUrl' => $loginUrl,
                'newTemporaryPassword' => $newTemporaryPassword,
            ]
        )->render();

        $learnerName = $learner->full_name ?: $learner->email;

        $text = implode("\n", [
            'SafeSky LMS',
            '',
            'Your password has been updated',
            '',
            'Hello ' . $learnerName . ',',
            '',
            'An administrator has updated the password for your SafeSky LMS account.',
            '',
            'Login information:',
            'Login / Email: ' . $learner->email,
            'New temporary password: ' . $newTemporaryPassword,
            '',
            'Login page:',
            $loginUrl,
            '',
            'IMPORTANT SECURITY NOTICE:',
            'Please sign in using the new password and change it as soon as possible.',
            'Do not share your login credentials with anyone.',
        ]);

        $this->sendEmail(
            $learner->email,
            'SafeSky LMS - Your Password Has Been Updated',
            $html,
            $text
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Individual Request - Created
    |--------------------------------------------------------------------------
    |
    | Notification sent to the LMS administrator when an individual learner
    | submits a new training request.
    |
    */

    public function sendIndividualRequestCreated(IndividualRequest $individualRequest, User $learner, string $recipientEmail): void 
    {
        /*
        |--------------------------------------------------------------------------
        | Load request courses
        |--------------------------------------------------------------------------
        */

        $individualRequest->loadMissing([
            'items.course',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Administrator login URL
        |--------------------------------------------------------------------------
        |
        | We use the login page because we do not want to invent an admin
        | Individual Request route that may not exist in the application.
        |
        */

        $adminUrl = route('login');

        /*
        |--------------------------------------------------------------------------
        | HTML template
        |--------------------------------------------------------------------------
        */

        $html = view(
            'emails.individual-requests.created',
            [
                'individualRequest' =>
                    $individualRequest,

                'learner' =>
                    $learner,

                'adminUrl' =>
                    $adminUrl,
            ]
        )->render();

        /*
        |--------------------------------------------------------------------------
        | Course list for text email
        |--------------------------------------------------------------------------
        */

        $courseLines = $individualRequest
            ->items
            ->map(function ($item) {
                return '- '
                    . (
                        $item->course?->title
                        ?? 'Course #' . $item->course_id
                    );
            })
            ->implode("\n");

        /*
        |--------------------------------------------------------------------------
        | Learner name
        |--------------------------------------------------------------------------
        */

        $learnerName =
            $learner->full_name
            ?: collect([
                $learner->first_name,
                $learner->middle_name,
                $learner->last_name,
            ])
                ->filter()
                ->implode(' ');

        if (!$learnerName) {
            $learnerName = $learner->email;
        }

        /*
        |--------------------------------------------------------------------------
        | Text version
        |--------------------------------------------------------------------------
        */

        $text = implode(
            "\n",
            [
                'SafeSky LMS',
                '',
                'New Individual Training Request',
                '',
                'Reference: '
                    . $individualRequest->reference,

                'Learner: '
                    . $learnerName,

                'Email: '
                    . $learner->email,

                'Courses: '
                    . $individualRequest->items->count(),

                '',
                $courseLines,
                '',
                'Learner note:',
                $individualRequest->learner_note
                    ?: '-',

                '',
                'Status: Pending',
                '',
                'Open SafeSky LMS:',
                $adminUrl,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Send
        |--------------------------------------------------------------------------
        */

        $this->sendEmail(
            $recipientEmail,

            'New Individual Training Request - '
                . $individualRequest->reference,

            $html,

            $text
        );
    }



    /*
    |--------------------------------------------------------------------------
    | Passed Exam - Certificates to Learner
    |--------------------------------------------------------------------------
    |
    | Sends every generated certificate PDF to the learner after a successful
    | final exam. The PDFs are supplied by LearnerSpaceController as raw bytes.
    |
    */
    public function sendPassedExamCertificatesToLearner(
        User $learner,
        Enrollment $enrollment,
        Exam $exam,
        array $attachments
    ): void {
        if (empty($attachments)) {
            throw new RuntimeException(
                'No certificate attachment was provided for the learner email.'
            );
        }

        $learner->loadMissing('company');
        $enrollment->loadMissing('course.category');

        $loginUrl = route('login');

        $html = view(
            'emails.certificates.exam-passed-learner',
            [
                'learner' => $learner,
                'enrollment' => $enrollment,
                'exam' => $exam,
                'loginUrl' => $loginUrl,
                'certificatesCount' => count($attachments),
            ]
        )->render();

        $learnerName = $learner->full_name ?: collect([
            $learner->first_name,
            $learner->middle_name,
            $learner->last_name,
        ])->filter()->implode(' ');

        if (!$learnerName) {
            $learnerName = $learner->email;
        }

        $courseTitle = $enrollment->course?->title ?? 'Training Course';

        $text = implode("\n", [
            'SafeSky LMS',
            '',
            'Congratulations ' . $learnerName . '!',
            '',
            'You passed the final exam for: ' . $courseTitle,
            'Score: ' . ((float) $exam->score) . '%',
            '',
            'Your certificate(s) are attached to this email.',
            'Certificates: ' . count($attachments),
            '',
            'SafeSky LMS:',
            $loginUrl,
        ]);

        $this->sendEmail(
            $learner->email,
            'Congratulations - Your Certificate(s) - ' . $courseTitle,
            $html,
            $text,
            $attachments
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Passed Exam - Certificates to Company
    |--------------------------------------------------------------------------
    |
    | If the learner belongs to a company and a company email is available,
    | send the same generated certificate PDF files to that company.
    |
    */
    public function sendPassedExamCertificatesToCompany(
        User $learner,
        Enrollment $enrollment,
        Exam $exam,
        string $companyEmail,
        ?string $companyName,
        array $attachments
    ): void {
        if (empty($attachments)) {
            throw new RuntimeException(
                'No certificate attachment was provided for the company email.'
            );
        }

        $enrollment->loadMissing('course.category');

        $learnerName = $learner->full_name ?: collect([
            $learner->first_name,
            $learner->middle_name,
            $learner->last_name,
        ])->filter()->implode(' ');

        if (!$learnerName) {
            $learnerName = $learner->email;
        }

        $courseTitle = $enrollment->course?->title ?? 'Training Course';

        $html = view(
            'emails.certificates.exam-passed-company',
            [
                'learner' => $learner,
                'enrollment' => $enrollment,
                'exam' => $exam,
                'companyName' => $companyName,
                'certificatesCount' => count($attachments),
            ]
        )->render();

        $text = implode("\n", [
            'SafeSky LMS',
            '',
            'Learner training completed successfully.',
            '',
            'Learner: ' . $learnerName,
            'Email: ' . $learner->email,
            'Course: ' . $courseTitle,
            'Score: ' . ((float) $exam->score) . '%',
            '',
            'The learner certificate(s) are attached to this email.',
            'Certificates: ' . count($attachments),
        ]);

        $this->sendEmail(
            $companyEmail,
            'Learner Certificate(s) - ' . $learnerName . ' - ' . $courseTitle,
            $html,
            $text,
            $attachments
        );
    }

}
