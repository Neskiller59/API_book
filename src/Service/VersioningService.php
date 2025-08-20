<?php
// src/Service/VersioningService.php
namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class VersioningService
{
    private RequestStack $requestStack;
    private string $defaultVersion;

    /**
     * Constructeur permettant de récupérer la requête courante
     * (pour extraire le champ "Accept" du header)
     * ainsi que le ParameterBagInterface pour récupérer la version
     * par défaut dans le fichier de configuration.
     *
     * @param RequestStack $requestStack
     * @param ParameterBagInterface $params
     */
    public function __construct(RequestStack $requestStack, ParameterBagInterface $params)
    {
        $this->requestStack = $requestStack;
        $this->defaultVersion = $params->get('default_api_version');
    }

    /**
     * Récupération de la version qui a été envoyée dans le header
     * "Accept" de la requête HTTP.
     *
     * @return string : le numéro de la version. Par défaut, la
     * version retournée est celle définie dans le fichier de
     * configuration services.yaml : "default_api_version"
     */
    public function getVersion(): string
    {
        $version = $this->defaultVersion;
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return $version;
        }

        $accept = $request->headers->get('Accept');
        if (!$accept) {
            return $version;
        }

        // Récupération du numéro de version dans la chaîne de caractères du Accept
        // Exemple : "application/json; test=bidule; version=2.0" => "2.0"
        $entetes = explode(';', $accept);

        foreach ($entetes as $value) {
            $value = trim($value);
            if (str_starts_with($value, 'version=')) {
                $version = explode('=', $value)[1];
                break;
            }
        }

        return $version;
    }
}
