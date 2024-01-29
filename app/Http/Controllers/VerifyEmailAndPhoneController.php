<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\PersonalAccessTokens;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyMail;
use App\Jobs\ProcessVerifyEmail;
use App\Jobs\ProcessSendSMS;
use App\Jobs\ProcessFactorAuthSMS;
use Dotenv\Exception\ValidationException;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use PDOException;
use App\Jobs\ProcessEmailSucces;

use function Laravel\Prompts\error;

class VerifyEmailAndPhoneController extends Controller
{

    /**
     * Verifica el email del usuario.
     * Crea un codigo de verificación y lo envia al usuario por SMS
     *
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function verifyEmail(Request $request)
    {
        try {
            // Verifica que la ruta tenga una firma valida
            if (!$request->hasValidSignature()) {
                abort(401);
            }
            $nRandom = rand(1000, 9999);
            $user = User::find($request->id);
            $user->code_phone = $nRandom;
            $user->save();
            // Crear una ruta temporal firmada para verificar el email y el telefono
            $url = URL::temporarySignedRoute(
                'sendCodeVerifyEmailAndPhone',
                now()->addMinutes(30),
                ['id' => $user->id]
            );
            //ProcessSendSMS::dispatch($user, $nRandom)->onConnection('database')->onQueue('sendSMS')->delay(now()->addseconds(30));
            return Inertia::render('VerifyEmailForm', ['user' => $user, 'url' => $url]);
        } catch (PDOException $e) {
            Log::channel('slackerror')->error($e->getMessage());
            return Inertia::render('LoginForm', [
                'error.PDO' => 'Error de Conexion'
            ]);
        } catch (QueryException $e) {
            Log::channel('slackerror')->error($e->getMessage());
            return Inertia::render('LoginForm', [
                'error.QueryE' => 'Datos Invalidos'
            ]);
        } catch (ValidationException $e) {
            Log::channel('slackerror')->error($e->getMessage());
            return Inertia::render('LoginForm', [
                'error.ValidationE' => 'Datos Invalidos'
            ]);
        } catch (Exception $e) {
            Log::channel('slackerror')->critical($e->getMessage());
            return Inertia::render('LoginForm', [
                'error.Exception' => 'Ocurrio un error'
            ]);
        }
    }

    /**
     * Recibe el codigo de verificación
     * y verifica que coincida con el codigo enviado al usuario por SMS
     * Activa la cuenta del usuario
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendCodeVerifyEmailAndPhone(Request $request)
    {
        try {
            // Verifica que la ruta tenga una firma valida
            if (!$request->hasValidSignature()) {
                abort(401);
            }
            $user = User::find($request->id);
            // Verifica que el codigo de verificación coincida con el codigo enviado al usuario por SMS
            if ($user->code_phone != $request->code_phone) {
                return Redirect::back()->withErrors('El codigo no coincide, se te enviara otro codigo');
            }
            $user->status = true;
            $user->save();
            if ($user->role_id == 1) {
                Log::channel('slackinfo')->warning('Se activo la cuenta del usuario administrador' . $user->name);
            }
            ProcessEmailSucces::dispatch($user)->onConnection('database')->onQueue('sendEmailSucces')->delay(now()->addseconds(30));
            return Redirect::route('login');
        } catch (PDOException $e) {
            Log::channel('slackerror')->error($e->getMessage());
            return Inertia::render('LoginForm', [
                'error.PDO' => 'Error de Conexion'
            ]);
        } catch (QueryException $e) {
            Log::channel('slackerror')->error($e->getMessage());
            return Inertia::render('LoginForm', [
                'error.QueryE' => 'Datos Invalidos'
            ]);
        } catch (ValidationException $e) {
            Log::channel('slackerror')->error($e->getMessage());
            return Inertia::render('LoginForm', [
                'error.ValidationE' => 'Datos Invalidos'
            ]);
        } catch (Exception $e) {
            Log::channel('slackerror')->critical($e->getMessage());
            return Inertia::render('LoginForm', [
                'error.Exception' => 'Ocurrio un error'
            ]);
        }
    }
}
