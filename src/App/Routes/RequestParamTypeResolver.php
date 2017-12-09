<?php

namespace Square1\Laravel\Connect\App\Routes;

use Closure;
use RuntimeException;
use BadMethodCallException;
use Illuminate\Support\Str;
use Illuminate\Support\Fluent;
use Illuminate\Validation\ValidationRuleParser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Square1\Laravel\Connect\Console\MakeClient;

class RequestParamTypeResolver
{
    /**
     * The initial rules provided.
     *
     * @var array
     */
    protected $initialRules;

    /**
     * The rules to be applied to the data.
     *
     * @var array
     */
    protected $rules;

    /**
     * The array of wildcard attributes with their asterisks expanded.
     *
     * @var array
     */
    protected $implicitAttributes = [];

    /**
     * The array of custom attribute names.
     *
     * @var array
     */
    public $customAttributes = [];

    /**
     * The array of custom displayable values.
     *
     * @var array
     */
    public $customValues = [];

    /**
     * All of the custom validator extensions.
     *
     * @var array
     */
    public $extensions = [];

    public $paramsType = [];

    /**
     * Map of rules types to data types supported by the client.
     *
     * @var array
     */
    protected $typesMap = [
        'File' => 'UploadedFile',
        'Image' => 'UploadedFile',
        'Integer' => 'int',
        'Boolean' => 'boolean',
        'Date' => 'timestamp',
        'Email' => 'string',
    ];

    /**
     * The validation rules that may be applied to files.
     *
     * @var array
     */
    protected $fileRules = [
        'File', 'Image', 'Mimes', 'Mimetypes', 'Min',
        'Max', 'Size', 'Between', 'Dimensions',
    ];

    /**
     * The validation rules that imply the field is required.
     *
     * @var array
     */
    protected $implicitRules = [
        'Required', 'Filled', 'RequiredWith', 'RequiredWithAll', 'RequiredWithout',
        'RequiredWithoutAll', 'RequiredIf', 'RequiredUnless', 'Accepted', 'Present',
    ];

    /**
     * The validation rules which depend on other fields as parameters.
     *
     * @var array
     */
    protected $dependentRules = [
        'RequiredWith', 'RequiredWithAll', 'RequiredWithout', 'RequiredWithoutAll',
        'RequiredIf', 'RequiredUnless', 'Confirmed', 'Same', 'Different', 'Unique',
        'Before', 'After', 'BeforeOrEqual', 'AfterOrEqual',
    ];

    private $makeClient;

    /**
     * Create a new Validator instance.
     *
     * @param \Illuminate\Contracts\Translation\Translator $translator
     * @param array                                        $data
     * @param array                                        $rules
     * @param array                                        $messages
     * @param array                                        $customAttributes
     */
    public function __construct(MakeClient $client, array $rules)
    {
        $this->makeClient = $client;
        $this->initialRules = $rules;

        $this->setRules($rules);
    }

    public function resolve()
    {
        // We'll spin through each rule, validating the attributes attached to that
        // rule. Any error messages will be added to the containers with each of
        // the other error messages, returning true if we don't have messages.
        foreach ($this->rules as $attribute => $rules) {
            $attribute = str_replace('\.', '->', $attribute);
            foreach ($rules as $rule) {
                $this->validateAttribute($attribute, $rule);
            }
        }
    }

    /**
     * Validate a given attribute against a rule.
     *
     * @param string $attribute
     * @param string $rule
     */
    protected function validateAttribute($attribute, $rule)
    {
        if (is_array($rule)) {
            foreach ($rule as $r) {
                $this->validateAttribute($attribute, $r);
            }

            return;
        }
        $this->makeClient->info("VALIDATING $attribute ->".json_encode($rule), 'vvv');

        //init values
        if (!isset($this->paramsType[$attribute])) {
            $this->paramsType[$attribute] = [];
            $this->paramsType[$attribute]['type'] = 'string';
        }

        list($rule, $parameters) = ValidationRuleParser::parse($rule);

        if ($rule == '') {
            return;
        }

        // First we will get the correct keys for the given attribute in case the field is nested in
        // an array. Then we determine if the given rule accepts other field names as parameters.
        // If so, we will replace any asterisks found in the parameters with the correct keys.
        if (($keys = $this->getExplicitKeys($attribute)) 
            && $this->dependsOnOtherFields($rule)
        ) {
            $parameters = $this->replaceAsterisksInParameters($parameters, $keys);
        }

        if ($rule === 'Array') {
            $this->paramsType[$attribute]['array'] = true;
        } elseif ($rule === 'Exists') {
            if (!empty($parameters) && is_array($parameters)) {
                //attempt to resolve the type from the Model class
                $classType = $this->makeClient->getModelClassFromTableName($parameters[0]);
                if (isset($classType)) {
                    $this->paramsType[$attribute]['type'] = $classType;
                } else {
                    //TODO we need to check what type is the key column in the table
                    $this->paramsType[$attribute]['type'] = 'int';
                }
                $this->paramsType[$attribute]['table'] = $parameters[0];
                $this->paramsType[$attribute]['key'] = isset($parameters[1]) ? $parameters[1] : 'id';
            }
        } elseif (isset($this->typesMap[$rule])) {
            $this->paramsType[$attribute]['type'] = $this->typesMap[$rule];
        } else {
            $this->paramsType[$attribute][$rule] = 1;
        }

        $this->makeClient->info("calling $rule ".json_encode($attribute).' '.json_encode($parameters), 'vvv');

        $this->makeClient->info(' ', 'vvv');
        $this->makeClient->info(' ', 'vvv');
    }

    /**
     * Determine if the given rule depends on other fields.
     *
     * @param string $rule
     *
     * @return bool
     */
    protected function dependsOnOtherFields($rule)
    {
        return in_array($rule, $this->dependentRules);
    }

    /**
     * Get the explicit keys from an attribute flattened with dot notation.
     *
     * E.g. 'foo.1.bar.spark.baz' -> [1, 'spark'] for 'foo.*.bar.*.baz'
     *
     * @param string $attribute
     *
     * @return array
     */
    protected function getExplicitKeys($attribute)
    {
        $pattern = str_replace('\*', '([^\.]+)', preg_quote($this->getPrimaryAttribute($attribute), '/'));

        if (preg_match('/^'.$pattern.'/', $attribute, $keys)) {
            array_shift($keys);
            $this->makeClient->info("getExplicitKeys $attribute => ".json_encode($keys), 'vvv');

            return $keys;
        }

        return [];
    }

    /**
     * Get the primary attribute name.
     *
     * For example, if "name.0" is given, "name.*" will be returned.
     *
     * @param string $attribute
     *
     * @return string
     */
    protected function getPrimaryAttribute($attribute)
    {
        $result = $attribute;
        foreach ($this->implicitAttributes as $unparsed => $parsed) {
            if (in_array($attribute, $parsed)) {
                $result = $unparsed;
                break;
            }
        }
        $this->makeClient->info("getPrimaryAttribute $attribute => $result", 'vvv');

        return $result;
    }

    /**
     * Replace each field parameter which has asterisks with the given keys.
     *
     * @param array $parameters
     * @param array $keys
     *
     * @return array
     */
    protected function replaceAsterisksInParameters(array $parameters, array $keys)
    {
        $this->makeClient->info('replaceAsterisksInParameters '.json_encode($parameters).'=>'.json_encode($keys), 'vvv');

        return array_map(
            function ($field) use ($keys) {
                return vsprintf(str_replace('*', '%s', $field), $keys);
            }, $parameters
        );
    }

    /**
     * Determine if the attribute is validatable.
     *
     * @param string $rule
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function isValidatable($rule, $attribute, $value)
    {
        return $this->presentOrRuleIsImplicit($rule, $attribute, $value) &&
               $this->passesOptionalCheck($attribute) &&
               $this->isNotNullIfMarkedAsNullable($attribute, $value) &&
               $this->hasNotFailedPreviousRuleIfPresenceRule($rule, $attribute);
    }

    /**
     * Determine if the field is present, or the rule implies required.
     *
     * @param string $rule
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function presentOrRuleIsImplicit($rule, $attribute, $value)
    {
        if (is_string($value) && trim($value) === '') {
            return $this->isImplicit($rule);
        }

        return $this->validatePresent($attribute, $value) || $this->isImplicit($rule);
    }

    /**
     * Determine if a given rule implies the attribute is required.
     *
     * @param string $rule
     *
     * @return bool
     */
    protected function isImplicit($rule)
    {
        return in_array($rule, $this->implicitRules);
    }

    /**
     * Determine if the attribute passes any optional check.
     *
     * @param string $attribute
     *
     * @return bool
     */
    protected function passesOptionalCheck($attribute)
    {
        if (!$this->hasRule($attribute, ['Sometimes'])) {
            return true;
        }

        $data = ValidationData::initializeAndGatherData($attribute, $this->data);

        return array_key_exists($attribute, $data)
                    || in_array($attribute, array_keys($this->data));
    }

    /**
     * Determine if the attribute fails the nullable check.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function isNotNullIfMarkedAsNullable($attribute, $value)
    {
        if (!$this->hasRule($attribute, ['Nullable'])) {
            return true;
        }

        return !is_null($value);
    }

    /**
     * Explode the explicit rule into an array if necessary.
     *
     * @param mixed $rule
     *
     * @return array
     */
    protected function explodeExplicitRule($rule)
    {
        $result = [];

        if (is_string($rule)) {
            $result = explode('|', $rule);
        } elseif (is_object($rule)) {
            $result = [$rule];
        } else {
            $result = $rule;
        }

        $this->makeClient->info("explodeExplicitRule $rule ".json_encode($result), 'vvv');

        return $result;
    }

    /**
     * Determine if the given attribute has a rule in the given set.
     *
     * @param string       $attribute
     * @param string|array $rules
     *
     * @return bool
     */
    public function hasRule($attribute, $rules)
    {
        return !is_null($this->getRule($attribute, $rules));
    }

    /**
     * Get a rule and its parameters for a given attribute.
     *
     * @param string       $attribute
     * @param string|array $rules
     *
     * @return array|null
     */
    protected function getRule($attribute, $rules)
    {
        if (!array_key_exists($attribute, $this->rules)) {
            return;
        }

        $rules = (array) $rules;

        foreach ($this->rules[$attribute] as $rule) {
            list($rule, $parameters) = ValidationRuleParser::parse($rule);

            if (in_array($rule, $rules)) {
                return [$rule, $parameters];
            }
        }
    }

    /**
     * Set the validation rules.
     *
     * @param array $rules
     *
     * @return $this
     */
    public function setRules(array $rules)
    {
        $this->initialRules = $rules;

        $this->rules = [];

        $this->addRules($rules);

        return $this;
    }

    /**
     * Parse the given rules and merge them into current rules.
     *
     * @param array $rules
     */
    protected function addRules($rules)
    {
        foreach ($rules as $param => $rule) {
            //clean up the wildcards
            $param = str_replace('\.\*', '', preg_quote($param));
            //check if param is already set
            if (!isset($this->rules[$param])) {
                $this->rules[$param] = [];
            }
            $exploded = $this->explodeExplicitRule($rule);
            $this->rules[$param][] = array_merge($this->rules[$param], $exploded);
        }
    }

    /**
     * Add conditions to a given field based on a Closure.
     *
     * @param string|array $attribute
     * @param string|array $rules
     * @param callable     $callback
     *
     * @return $this
     */
    public function sometimes($attribute, $rules, callable $callback)
    {
        $payload = new Fluent($this->getData());

        if (call_user_func($callback, $payload)) {
            foreach ((array) $attribute as $key) {
                $this->addRules([$key => $rules]);
            }
        }

        return $this;
    }

    /**
     * Register an array of custom validator extensions.
     *
     * @param array $extensions
     */
    public function addExtensions(array $extensions)
    {
        if ($extensions) {
            $keys = array_map('\Illuminate\Support\Str::snake', array_keys($extensions));

            $extensions = array_combine($keys, array_values($extensions));
        }

        $this->extensions = array_merge($this->extensions, $extensions);
    }

    /**
     * Register an array of custom implicit validator extensions.
     *
     * @param array $extensions
     */
    public function addImplicitExtensions(array $extensions)
    {
        $this->addExtensions($extensions);

        foreach ($extensions as $rule => $extension) {
            $this->implicitRules[] = Str::studly($rule);
        }
    }

    /**
     * Register a custom validator extension.
     *
     * @param string          $rule
     * @param \Closure|string $extension
     */
    public function addExtension($rule, $extension)
    {
        $this->extensions[Str::snake($rule)] = $extension;
    }

    /**
     * Register a custom implicit validator extension.
     *
     * @param string          $rule
     * @param \Closure|string $extension
     */
    public function addImplicitExtension($rule, $extension)
    {
        $this->addExtension($rule, $extension);

        $this->implicitRules[] = Str::studly($rule);
    }

    /**
     * Register an array of custom validator message replacers.
     *
     * @param array $replacers
     */
    public function addReplacers(array $replacers)
    {
        if ($replacers) {
            $keys = array_map('\Illuminate\Support\Str::snake', array_keys($replacers));

            $replacers = array_combine($keys, array_values($replacers));
        }

        $this->replacers = array_merge($this->replacers, $replacers);
    }

    /**
     * Register a custom validator message replacer.
     *
     * @param string          $rule
     * @param \Closure|string $replacer
     */
    public function addReplacer($rule, $replacer)
    {
        $this->replacers[Str::snake($rule)] = $replacer;
    }

    /**
     * Set the custom messages for the validator.
     *
     * @param array $messages
     */
    public function setCustomMessages(array $messages)
    {
        $this->customMessages = array_merge($this->customMessages, $messages);
    }

    /**
     * Set the custom attributes on the validator.
     *
     * @param array $attributes
     *
     * @return $this
     */
    public function setAttributeNames(array $attributes)
    {
        $this->customAttributes = $attributes;

        return $this;
    }

    /**
     * Add custom attributes to the validator.
     *
     * @param array $customAttributes
     *
     * @return $this
     */
    public function addCustomAttributes(array $customAttributes)
    {
        $this->customAttributes = array_merge($this->customAttributes, $customAttributes);

        return $this;
    }

    /**
     * Set the custom values on the validator.
     *
     * @param array $values
     *
     * @return $this
     */
    public function setValueNames(array $values)
    {
        $this->customValues = $values;

        return $this;
    }

    /**
     * Add the custom values for the validator.
     *
     * @param array $customValues
     *
     * @return $this
     */
    public function addCustomValues(array $customValues)
    {
        $this->customValues = array_merge($this->customValues, $customValues);

        return $this;
    }

    /**
     * Set the fallback messages for the validator.
     *
     * @param array $messages
     */
    public function setFallbackMessages(array $messages)
    {
        $this->fallbackMessages = $messages;
    }

    /**
     * Get the Presence Verifier implementation.
     *
     * @return \Illuminate\Validation\PresenceVerifierInterface
     *
     * @throws \RuntimeException
     */
    public function getPresenceVerifier()
    {
        if (!isset($this->presenceVerifier)) {
            throw new RuntimeException('Presence verifier has not been set.');
        }

        return $this->presenceVerifier;
    }

    /**
     * Get the Presence Verifier implementation.
     *
     * @param string $connection
     *
     * @return \Illuminate\Validation\PresenceVerifierInterface
     *
     * @throws \RuntimeException
     */
    protected function getPresenceVerifierFor($connection)
    {
        return tap(
            $this->getPresenceVerifier(), function ($verifier) use ($connection) {
                $verifier->setConnection($connection);
            }
        );
    }

    /**
     * Set the Presence Verifier implementation.
     *
     * @param \Illuminate\Validation\PresenceVerifierInterface $presenceVerifier
     */
    public function setPresenceVerifier(PresenceVerifierInterface $presenceVerifier)
    {
        $this->presenceVerifier = $presenceVerifier;
    }

    /**
     * Call a custom validator extension.
     *
     * @param string $rule
     * @param array  $parameters
     *
     * @return bool|null
     */
    protected function callExtension($rule, $parameters)
    {
        $callback = $this->extensions[$rule];

        if ($callback instanceof Closure) {
            return call_user_func_array($callback, $parameters);
        } elseif (is_string($callback)) {
            return $this->callClassBasedExtension($callback, $parameters);
        }
    }

    /**
     * Call a class based validator extension.
     *
     * @param string $callback
     * @param array  $parameters
     *
     * @return bool
     */
    protected function callClassBasedExtension($callback, $parameters)
    {
        list($class, $method) = Str::parseCallback($callback, 'validate');

        return call_user_func_array([$this->container->make($class), $method], $parameters);
    }

    /**
     * Handle dynamic calls to class methods.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        $rule = Str::snake(substr($method, 8));

        if (isset($this->extensions[$rule])) {
            return $this->callExtension($rule, $parameters);
        }

        throw new BadMethodCallException("Method [$method] does not exist.");
    }
}
