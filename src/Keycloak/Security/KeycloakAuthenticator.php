<?php

declare(strict_types=1);

namespace App\Keycloak\Security;

use App\Keycloak\Constants;
use App\Keycloak\Exception\InvalidState;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use function is_string;

final class KeycloakAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private Keycloak $provider,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $redirectUrl = $this->provider->getAuthorizationUrl([
            'redirect_uri' => $this->urlGenerator->generate(Constants::CALLBACK_ROUTE, referenceType: UrlGeneratorInterface::ABSOLUTE_URL),
            'scope' => 'email',
        ]);

        $session = $request->getSession();
        $session->set(Constants::REDIRECT_TO, $request->getUri());
        $session->set(Constants::OAUTH_2_STATE, $this->provider->getState());

        return new RedirectResponse($redirectUrl);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request): bool
    {
        return Constants::CALLBACK_ROUTE === $request->attributes->get('_route')
            && $request->query->has('code')
            && $request->query->has('state')
            ;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(Request $request): PassportInterface
    {
        $session = $request->getSession();

        if ($request->query->get('state') !== $session->remove(Constants::OAUTH_2_STATE)) {
            throw new InvalidState();
        }

        $code = (string) $request->query->get('code');

        return new SelfValidatingPassport(new UserBadge($code));
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->redirect($request);
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($exception instanceof InvalidState) {
            return $this->redirect($request);
        }

        throw $exception;
    }

    private function redirect(Request $request): RedirectResponse
    {
        $redirectTo = $request->getSession()->remove(Constants::REDIRECT_TO);

        $redirectTo = is_string($redirectTo) ? $redirectTo : $this->urlGenerator->generate('easyadmin');

        return new RedirectResponse($redirectTo);
    }
}
