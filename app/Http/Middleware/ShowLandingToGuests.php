<?php

namespace App\Http\Middleware;

use App\Http\Controllers\LandingController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Na raiz, visitantes veem a landing e quem está logado segue para a busca.
 *
 * Fica no lugar do "auth" porque o Laravel adianta o "auth" para antes do
 * HandleInertiaRequests; aqui os props compartilhados já existem.
 */
class ShowLandingToGuests
{
    public function __construct(private readonly LandingController $landing) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() === null) {
            return ($this->landing)()->toResponse($request);
        }

        return $next($request);
    }
}
