<?php declare(strict_types = 1);
namespace PharIo\Phive;

class AuthXmlConfig implements AuthConfig {
    /** @var XmlFile */
    private $xmlFile;

    /**
     * AuthXmlConfig constructor.
     */
    public function __construct(XmlFile $xmlFile) {
        $this->xmlFile = $xmlFile;
    }

    public function hasAuthentication(string $domain): bool {
        $query  = \sprintf('//phive:domain[@host="%s"]', $domain);
        $result = $this->xmlFile->query($query);

        return $result->count() > 0;
    }

    /**
     * @throws AuthException
     */
    public function getAuthentication(string $domain): Authentication {
        if (!$this->hasAuthentication($domain)) {
            throw new AuthException(\sprintf('No authentication data for %s', $domain));
        }

        $query  = \sprintf('//phive:domain[@host="%s"]', $domain);
        $result = $this->xmlFile->query($query);

        /** @var \DOMElement $auth */
        $auth = $result->item(0);

        if (!$auth->hasAttribute('type')) {
            throw new AuthException(\sprintf('Authentication data for %s is invalid', $domain));
        }

        $authType = $auth->getAttribute('type');

        if ($authType === 'Basic') {
            return $this->handleBasicAuthentication($domain, $auth);
        }

        if (!$auth->hasAttribute('credentials') || empty($auth->getAttribute('credentials'))) {
            throw new AuthException(\sprintf('Authentication data for %s is invalid', $domain));
        }

        $authCredentials = $auth->getAttribute('credentials');

        switch ($authType) {
            case 'Token':
                return new TokenAuthentication($domain, $authCredentials);
            case 'Bearer':
                return new BearerAuthentication($domain, $authCredentials);
            default:
                throw new AuthException(\sprintf('Invalid authentication type for %s', $domain));
        }
    }

    /**
     * @throws AuthException
     */
    private function handleBasicAuthentication(string $domain, \DOMElement $node): Authentication {
        if (
            $node->hasAttribute('username')
            && !empty($username = $node->getAttribute('username'))
            && \strpos($username, ':') === false
            && $node->hasAttribute('password')
            && !empty($node->getAttribute('password'))
        ) {
            return BasicAuthentication::fromLoginPassword($domain, $username, $node->getAttribute('password'));
        }

        if ($node->hasAttribute('credentials') && !empty($node->getAttribute('credentials'))) {
            return new BasicAuthentication($domain, $node->getAttribute('credentials'));
        }

        throw new AuthException(\sprintf('Basic authentication data for %s is invalid', $domain));
    }
}
