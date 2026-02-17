<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamParticipantController;
use App\Http\Controllers\ExamResultController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DncExamController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php';

// A partir de aquí, TODO debe pasar por auth + verified
Route::middleware(['auth'])->group(function () {

    // Dashboard principal
    Route::get('/', [DashboardController::class, 'index'])
        ->name('home');
    Route::get('/', [DashboardController::class, 'index'])
        ->name('dashboard');
    Route::get('/dncs/{dnc}', [DncExamController::class, 'list'])
        ->name('dncs.list');
    Route::get('/dnc/{dnc}/exams', [DncExamController::class, 'listExam'])
        ->name('dncs.exams.list');

    // Perfil del usuario
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    // Exámenes
    Route::get('/exams/{exam}/preview', [ExamParticipantController::class, 'preview'])
        ->name('exam.preview');
    Route::get('/exams/{exam}/start', [ExamParticipantController::class, 'start'])
        ->name('exam.start');
    Route::post('/exams/{exam}/save/{page}', [ExamParticipantController::class, 'savePage'])
        ->name('exam.savePage');
    Route::post('/exams/{exam}/attempts/answer', [ExamParticipantController::class, 'saveAnswer'])
        ->name('exam.saveAnswer');
    Route::post('/exams/{exam}/finish-ajax', [ExamParticipantController::class, 'finishAjax'])
        ->name('exam.finishAjax');
    Route::get('/exams/{exam}/finish/{attempt?}', [ExamParticipantController::class, 'finish'])
        ->name('exam.finish');
    // Descarga de áreas de mejora (Excel)
    Route::get('attempts/{attempt}/download-wrong', [ExamResultController::class, 'downloadWrongByAttempt'])
        ->name('attempt.downloadWrong');
    // Descarga de resultado completo en PDF
    Route::post('attempts/{attempt}/download-pdf', [ExamResultController::class, 'downloadPdfByAttempt'])
        ->name('attempt.downloadPdf');
});
