<?php

namespace DevCoder;

/**
 * Class Route
 * @package DevCoder
 */
class Route
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var array<string>
     */
    protected $parameters = [];

    /**
     * @var array<string>
     */
    protected $methods = [];

    /**
     * @var array<string>
     */
    protected $vars = [];

    /**
     * Route constructor.
     * @param string $name
     * @param string $path
     * @param array $parameters
     *    $parameters = [
     *      0 => (string) Controller name : HomeController::class.
     *      1 => (string|null) Method name or null if invoke method
     *    ]
     * @param array $methods
     */
    public function __construct(string $name, string $path, array $parameters, array $methods = ['GET'])
    {
        if ($methods === []) {
            throw new \InvalidArgumentException('HTTP methods argument was empty; must contain at least one method');
        }
        $this->name = $name;
        $this->path = $path;
        $this->parameters = $parameters;
        $this->methods = $methods;
    }

    public function match(string $path, string $method): bool
    {
        if (
            in_array($method, $this->getMethods()) &&
            preg_match('#^' . $this->generateRegex() . '$#sD', self::trimPath($path), $matches)
        ) {

            $values = array_filter($matches, function ($key) {
                return is_string($key);
            }, ARRAY_FILTER_USE_KEY);

            foreach ($values as $key => $value) {
                $this->addVar($key, $value);
            }

            return true;
        }
        return false;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getVarsNames(): array
    {
        preg_match_all('/{[^}]*}/', $this->path, $matches);
        return reset($matches) ?: [];
    }

    public function hasVars(): bool
    {
        return $this->getVarsNames() !== [];
    }

    public function getVars(): array
    {
        return $this->vars;
    }

    private function addVar(string $key, string $value): self
    {
        $this->vars[$key] = $value;
        return $this;
    }

    private function generateRegex(): string
    {
        $regex = $this->path;
        foreach ($this->getVarsNames() as $variable) {
            $varName = trim($variable, '{\}');
            $regex = str_replace($variable, '(?P<' . $varName . '>[^/]++)', $regex);
        }
        return $regex;
    }

    private static function trimPath(string $path): string
    {
        return '/' . rtrim(ltrim(trim($path), '/'), '/');
    }
}
