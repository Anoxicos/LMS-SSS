<?php

use App\Http\Controllers\BankController;
use App\Http\Controllers\CategoryControlle;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyRequestController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\LearnerController;
use App\Http\Controllers\LearnerSpaceController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ScormCourseController;
use App\Http\Controllers\ScormTrackingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VelzonRoutesController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\IndividualRequestCheckoutController;
use App\Http\Controllers\IndividualRequestController;
use App\Http\Controllers\LearnerAccountController;
use App\Http\Controllers\LearnerRegistrationController;

use App\Http\Controllers\Auth\EmailVerificationCodeController;

use Illuminate\Support\Facades\Mail;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\Auth\XOAuth2Authenticator;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::middleware('guest')
    ->prefix('learner')
    ->name('learner.')
    ->controller(LearnerRegistrationController::class)
    ->group(function () {
        Route::get('/register', 'create')
            ->name('register');

        Route::post('/register', 'store')
            ->middleware('throttle:5,1')
            ->name('register.store');
});


Route::controller(VelzonRoutesController::class)->group(function () {
    // dashboards
   
        Route::get('/', 'dashboard')->name('dashboard');
        Route::get('/courses', 'courses')->name('courses');
        Route::get('/courses/{courseHashid}','courseDetails')->name('public.courses.show');
});



Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified',])->group(function () {

    Route::prefix('roles')->name('roles.')->group(function(){
        Route::controller(RoleController::class)->group(function(){
            Route::get('/' , "index")->name('index');
            Route::post('/store' , "store")->name('store');
            Route::get('/role/edit/{role}' , "editRole")->name('editRole');
            Route::get('/role/show/{role}' , "showRole")->name('showRole');
            Route::put('/role/store/edit/{role}' , 'storeEditRole')->name('storeEditRole');
        });
    });

    Route::prefix('companies')->name('companies.')->group(function () {
        Route::controller(CompanyController::class)->group(function () {
            Route::get('/', 'index')->middleware('permission:read company')->name('index');
            Route::get('/show/{hashid}', 'show')->middleware('permission:read company')->name('show');
            Route::post('/store', 'store')->middleware('permission:create company')->name('store');
            Route::post('/update/{hashid}', 'update')->middleware('permission:update company')->name('update');
            Route::delete('/delete/{hashid}', 'destroy')->middleware('permission:delete company')->name('delete');
        });
    });

    Route::prefix('users')->name('users.')->group(function () {
        Route::controller(UserController::class)->group(function () {
            Route::get('/', 'index')->middleware('permission:read user')->name('index');
            Route::post('/store', 'store')->middleware('permission:create user')->name('store');
            Route::post('/update/{hashid}', 'update')->middleware('permission:update user')->name('update');
            Route::delete('/delete/{hashid}', 'destroy')->middleware('permission:delete user')->name('delete');
        });
    });

    Route::prefix('learners')->name('learners.')->group(function () {
        Route::controller(LearnerController::class)->group(function () {
            Route::get('/', 'index')->middleware('permission:read learner')->name('index');
            Route::get('/show/{hashid}', 'show')->middleware('permission:read learner')->name('show');
            Route::post('/store', 'store')->middleware('permission:create learner')->name('store');
            Route::post('/update/{hashid}', 'update')->middleware('permission:update learner')->name('update');
            Route::delete('/delete/{hashid}', 'destroy')->middleware('permission:delete learner')->name('delete');
        });
    });

  
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::controller(CategoryControlle::class)->group(function () {
            Route::get('/', 'index')->middleware('permission:read category')->name('index');
            Route::post('/store', 'store')->middleware('permission:create category')->name('store');
            Route::post('/update/{hashid}', 'update')->middleware('permission:update category')->name('update');
            Route::delete('/delete/{hashid}', 'destroy')->middleware('permission:delete category')->name('delete');
        });
    });



    Route::prefix('admin/courses')->name('courses.')->group(function () {
        Route::controller(CourseController::class)->group(function () {
            Route::get('/', 'index')
                ->middleware('permission:read course')
                ->name('index');

            Route::get('/create', 'create')
                ->middleware('permission:create course')
                ->name('create');

            Route::post('/store', 'store')
                ->middleware('permission:create course')
                ->name('store');

            Route::get('/edit/{course}', 'edit')
                ->middleware('permission:update course')
                ->name('edit');

            Route::post('/update/{course}', 'updateBasicInfo')
                ->middleware('permission:update course')
                ->name('update');

            Route::post('/{course}/chapters/store', 'storeChapter')
                ->middleware('permission:update course')
                ->name('chapters.store');

            Route::post('/{course}/chapters/{chapter}/update', 'updateChapter')
                ->middleware('permission:update course')
                ->name('chapters.update');

            Route::delete('/{course}/chapters/{chapter}/delete', 'destroyChapter')
                ->middleware('permission:update course')
                ->name('chapters.delete');

            Route::post('/{course}/chapters/reorder', 'reorderChapters')
                ->middleware('permission:update course')
                ->name('chapters.reorder');

            Route::post('/{course}/chapters/{chapter}/slides/store', 'storeSlide')
                ->middleware('permission:update course')
                ->name('slides.store');

            Route::post('/{course}/slides/{slide}/update', 'updateSlide')
                ->middleware('permission:update course')
                ->name('slides.update');

            Route::delete('/{course}/slides/{slide}/delete', 'destroySlide')
                ->middleware('permission:update course')
                ->name('slides.delete');

            Route::post('/{course}/chapters/{chapter}/slides/reorder', 'reorderSlides')
                ->middleware('permission:update course')
                ->name('slides.reorder');

            Route::post('/{course}/slides/{slide}/voice-over/generate', 'generateSlideVoiceOver')
                ->middleware('permission:update course')
                ->name('slides.voice-over.generate');

            Route::delete('/{course}/slides/{slide}/voice-over/delete', 'deleteSlideVoiceOver')
                ->middleware('permission:update course')
                ->name('slides.voice-over.delete');

            Route::post('/{course}/plan/update', 'updatePlan')
                ->middleware('permission:update course')
                ->name('plan.update');

            Route::post('/{course}/plan/details/store', 'storePlanDetail')
                ->middleware('permission:update course')
                ->name('plan.details.store');

            Route::post('/{course}/plan/details/{detail}/update', 'updatePlanDetail')
                ->middleware('permission:update course')
                ->name('plan.details.update');

            Route::delete('/{course}/plan/details/{detail}/delete', 'destroyPlanDetail')
                ->middleware('permission:update course')
                ->name('plan.details.delete');

            Route::delete('/delete/{course}', 'destroy')
                ->middleware('permission:delete course')
                ->name('delete');

            Route::get('/show/{hashid}', 'show')
                ->middleware('permission:read course')
                ->name('show');
        });

        Route::prefix('scorm')
            ->name('scorm.')
            ->controller(ScormCourseController::class)
            ->group(function () {
                Route::get('/', 'index')
                    ->middleware('permission:read course')
                    ->name('index');

                Route::get('/create', 'create')
                    ->middleware('permission:create course')
                    ->name('create');

                Route::post('/', 'store')
                    ->middleware('permission:create course')
                    ->name('store');

                Route::get('/{hashid}/edit', 'edit')
                    ->middleware('permission:update course')
                    ->name('edit');

                Route::post('/{hashid}/update', 'update')
                    ->middleware('permission:update course')
                    ->name('update');

                Route::post('/{hashid}/plan/update', 'updatePlan')
                    ->middleware('permission:update course')
                    ->name('plan.update');

                Route::post('/{hashid}/plan/details/store', 'storePlanDetail')
                    ->middleware('permission:update course')
                    ->name('plan.details.store');

                Route::post('/{hashid}/plan/details/{detail}/update', 'updatePlanDetail')
                    ->middleware('permission:update course')
                    ->name('plan.details.update');

                Route::delete('/{hashid}/plan/details/{detail}/delete', 'destroyPlanDetail')
                    ->middleware('permission:update course')
                    ->name('plan.details.delete');

                Route::get('/{hashid}/show', 'show')
                    ->middleware('permission:read course')
                    ->name('show');

                Route::delete('/{hashid}/delete', 'destroy')
                    ->middleware('permission:delete course')
                    ->name('delete');
        });
    });

   


 

    Route::prefix('banks')->name('banks.')->group(function () {
        Route::get('/', [BankController::class, 'index'])->name('index');

        Route::post('/', [BankController::class, 'store'])->name('store');
        Route::post('/{bank}/update', [BankController::class, 'update'])->name('update');
        Route::delete('/{bank}/delete', [BankController::class, 'destroy'])->name('delete');

        Route::get('/{bank}/questions', [BankController::class, 'questions'])->name('questions');

        Route::post('/{bank}/questions', [BankController::class, 'storeQuestion'])->name('questions.store');
        Route::post('/{bank}/questions/{question}/update', [BankController::class, 'updateQuestion'])->name('questions.update');
        Route::delete('/{bank}/questions/{question}/delete', [BankController::class, 'destroyQuestion'])->name('questions.delete');

        Route::get('/{bank}/questions/export', [BankController::class, 'exportQuestions'])->name('questions.export');
        Route::post('/{bank}/questions/import', [BankController::class, 'importQuestions'])->name('questions.import');
    });


    Route::prefix('company-requests')->name('company-requests.')->group(function () {
        Route::controller(CompanyRequestController::class)->group(function () {
            Route::get('/', 'index')
                ->middleware('permission:read company request')
                ->name('index');

            Route::get('/create', 'create')
                ->middleware('permission:create company request')
                ->name('create');

            Route::post('/store', 'store')
                ->middleware('permission:create company request')
                ->name('store');

            Route::get('/show/{hashid}', 'show')
                ->middleware('permission:read company request')
                ->name('show');

            Route::post('/approve/{hashid}', 'approve')
                ->middleware('permission:approve company request')
                ->name('approve');

            Route::post('/reject/{hashid}', 'reject')
                ->middleware('permission:approve company request')
                ->name('reject');

            Route::get('/edit/{hashid}', 'edit')
                ->middleware('permission:update company request')
                ->name('edit');

            Route::post('/update/{hashid}', 'update')
                ->middleware('permission:update company request')
                ->name('update');
        });
    });



   

  

    Route::prefix('learner-space')
        ->name('learner-space.')
        ->group(function () {
            Route::controller(LearnerSpaceController::class)
            ->group(function () {
                Route::get('/dashboard', 'dashboard')
                    ->name('dashboard');

                Route::get('/my-courses', 'myCourses')
                    ->name('my-courses');

                /*
                |--------------------------------------------------------------------------
                | Course viewer
                |--------------------------------------------------------------------------
                |
                | Cette route affiche :
                | - courseViewer.vue pour les cours normaux ;
                | - scormCourseViewer.vue pour les cours SCORM.
                |
                */
                Route::get(
                    '/enrollment/{enrollmentHashid}/course',
                    'courseViewer'
                )->name('course-viewer');

                /*
                |--------------------------------------------------------------------------
                | Course completion page
                |--------------------------------------------------------------------------
                */
                Route::get(
                    '/enrollment/{enrollmentHashid}/finish',
                    'courseFinish'
                )->name('course-finish');

                /*
                |--------------------------------------------------------------------------
                | Exam routes
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/enrollment/{enrollmentHashid}/exam/start',
                    'startExam'
                )->name('exam.start');

                Route::get(
                    '/exam/{examHashid}',
                    'showExam'
                )->name('exam.show');

                /*
                | Sauvegarde automatique d’une réponse.
                | Appelée à chaque sélection ou suppression de réponse.
                */
                Route::post(
                    '/exam/{examHashid}/answer',
                    'saveExamAnswer'
                )->name('exam.answer');

                /*
                | Soumission manuelle ou automatique à la fin du temps.
                */
                Route::post(
                    '/exam/{examHashid}/submit',
                    'submitExam'
                )->name('exam.submit');

                /*
                |--------------------------------------------------------------------------
                | Normal course progress routes
                |--------------------------------------------------------------------------
                |
                | Ces routes sont utilisées uniquement pour les cours normaux.
                |
                */
                Route::post(
                    '/enrollment/{enrollmentHashid}/slide/{slide}/viewed',
                    'markSlideViewed'
                )->name('slide.viewed');

                Route::post(
                    '/enrollment/{enrollmentHashid}/slide/{slide}/completed',
                    'markSlideCompleted'
                )->name('slide.completed');

                /*
                |--------------------------------------------------------------------------
                | Certificate
                |--------------------------------------------------------------------------
                */
                Route::get(
                    '/enrollment/{enrollmentHashid}/certificate/download',
                    'downloadCertificate'
                )->name('certificate.download');

              
            });
    });


    Route::prefix('learner-space')
        ->name('learner-space.')
        ->group(function () {
            Route::controller(ScormTrackingController::class)
            ->group(function () {
                    Route::post('/enrollments/{enrollmentHashid}/scorm/commit' , 'commit')->name('scorm.commit');
            });
    });


    // 


    Route::prefix('learner-account')
        ->name('learner-account.')
        ->controller(LearnerAccountController::class)
        ->group(function () {
            Route::get('/complete', 'edit')
                ->name('edit');

            Route::post('/complete', 'update')
                ->name('update');
        });

    Route::prefix('individual-training-request')
        ->name('public.individual-requests.')
        ->controller(IndividualRequestCheckoutController::class)
        ->group(function () {
            Route::get('/', 'create')
                ->name('create');

            Route::post('/', 'store')
                ->middleware('throttle:10,1')
                ->name('store');

            Route::get('/success/{reference}', 'success')
                ->name('success');
    });

    Route::prefix('individual-requests')
        ->name('individual-requests.')
        ->controller(IndividualRequestController::class)
        ->group(function () {
            Route::get('/', 'index')
                ->name('index');

            Route::get('/show/{hashid}', 'show')
                ->name('show');

            Route::post('/review/{hashid}', 'review')
                ->name('review');

            Route::get('/passport/{hashid}', 'downloadPassport')
                ->name('passport');
    });
     
});



    Route::post('/email/verify/code',
        [
            EmailVerificationCodeController::class,
            'verify'
        ]
    )
        ->middleware([
            'auth',
            'throttle:5,1',
        ])
        ->name(
            'verification.code.verify'
        );


    


    Route::get('/test-microsoft-oauth-mail', function () {

        try {

            /*
            |--------------------------------------------------------------------------
            | 1. Récupérer le token OAuth Microsoft
            |--------------------------------------------------------------------------
            */

            $tenantId = env('MICROSOFT_TENANT_ID');
            $clientId = env('MICROSOFT_CLIENT_ID');
            $clientSecret = env('MICROSOFT_CLIENT_SECRET');

            $tokenResponse = Http::asForm()->post(
                "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
                [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'scope' => 'https://outlook.office365.com/.default',
                    'grant_type' => 'client_credentials',
                ]
            );

            if ($tokenResponse->failed()) {
                return response()->json([
                    'success' => false,
                    'step' => 'oauth_token',
                    'status' => $tokenResponse->status(),
                    'error' => $tokenResponse->json(),
                ], 500);
            }

            $accessToken = $tokenResponse->json('access_token');

            if (!$accessToken) {
                return response()->json([
                    'success' => false,
                    'step' => 'oauth_token',
                    'message' => 'Access token not returned by Microsoft.',
                ], 500);
            }

            /*
            |--------------------------------------------------------------------------
            | 2. Configurer SMTP Microsoft avec XOAUTH2
            |--------------------------------------------------------------------------
            */

            $transport = new EsmtpTransport(
                'smtp.office365.com',
                587
            );

            $transport->setUsername(
                env('MICROSOFT_MAIL_USERNAME')
            );

            // Avec XOAUTH2, le token est utilisé à la place du mot de passe.
            $transport->setPassword($accessToken);

            // Important : forcer XOAUTH2 uniquement.
            $transport->setAuthenticators([
                new XOAuth2Authenticator(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | 3. Construire l'email
            |--------------------------------------------------------------------------
            */

            $email = (new Email())
                ->from(env('MICROSOFT_MAIL_FROM'))
                ->to('abdellatifat160599@gmail.com')
                ->subject('Test OAuth2 - SafeSky LMS')
                ->text(
                    'Test réussi : cet email a été envoyé depuis Laravel '
                    . 'avec Microsoft 365 OAuth2 / XOAUTH2.'
                );

            /*
            |--------------------------------------------------------------------------
            | 4. Envoyer
            |--------------------------------------------------------------------------
            */

            $mailer = new Mailer($transport);

            $mailer->send($email);

            return response()->json([
                'success' => true,
                'message' => 'Email envoyé avec succès via Microsoft OAuth2.',
                'from' => env('MICROSOFT_MAIL_FROM'),
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'step' => 'smtp',
                'error' => $e->getMessage(),
            ], 500);
        }
    });