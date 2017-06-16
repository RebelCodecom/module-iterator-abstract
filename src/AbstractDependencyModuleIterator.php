<?php

namespace RebelCode\Modular\Iterator;

use ArrayAccess;
use Dhii\Collection\AbstractTraversableCollection;
use Dhii\Modular\Module\ModuleInterface;

/**
 * Basic functionality for a module iterator that handles dependencies.
 *
 * @since [*next-version*]
 */
abstract class AbstractDependencyModuleIterator extends AbstractTraversableCollection
{
    /**
     * The modules that have already been served, mapped by their keys.
     *
     * @since [*next-version*]
     *
     * @var ModuleInterface[]
     */
    protected $servedModules;

    /**
     * A cache for the module that is currently being served.
     *
     * @since [*next-version*]
     *
     * @var ModuleInterface
     */
    protected $current;

    /**
     * A map of the module instances mapped using the module keys.
     *
     * @since [*next-version*]
     *
     * @var ModuleInterface[]
     */
    protected $moduleMap;

    /**
     * Internal parameterless constructor.
     *
     * @since [*next-version*]
     */
    protected function _construct()
    {
        parent::_construct();
    }

    /**
     * Retrieves the map of modules, mapped by their keys.
     *
     * @since [*next-version*]
     *
     * @return array
     */
    protected function _getModuleMap()
    {
        if (is_null($this->moduleMap)) {
            $this->moduleMap = $this->_createModuleMap($this->_getCachedItems());
        }

        return $this->moduleMap;
    }

    /**
     * Creates a map of modules, mapped by their keys, from a given module list.
     *
     * @since [*next-version*]
     *
     * @param array $modules The list of modules.
     *
     * @return array The modules, mapped by their keys.
     */
    protected function _createModuleMap(array $modules)
    {
        $map = array();

        foreach ($modules as $_module) {
            $map[$_module->getKey()] = $_module;
        }

        return $map;
    }

    /**
     * Clears the map of modules.
     *
     * @since [*next-version*]
     *
     * @return $this
     */
    protected function _clearModuleMap()
    {
        $this->moduleMap = null;

        return $this;
    }

    /**
     * Retrieves the module with a specific key.
     *
     * @since [*next-version*]
     *
     * @param string $key The module key.
     *
     * @return ModuleInterface|null The module with the given key or null if the module key was not found.
     */
    protected function _getModuleByKey($key)
    {
        $moduleMap = $this->_getModuleMap();

        return isset($moduleMap[$key])
            ? $moduleMap[$key]
            : null;
    }

    /**
     * Retrieves the list of modules that have already been served.
     *
     * @since [*next-version*]
     *
     * @return ModuleInterface[] An array of module instances mapped by their keys.
     */
    protected function _getServedModules()
    {
        return $this->servedModules;
    }

    /**
     * Sets the modules that have already been served.
     *
     * @since [*next-version*]
     *
     * @param ModuleInterface[] $served An array of module instances mapped by their keys.
     *
     * @return $this
     */
    protected function _setServedModules(array $served)
    {
        $this->servedModules = $served;

        return $this;
    }

    /**
     * Adds a module to the list of served modules.
     *
     * @param ModuleInterface $module The module instance.
     *
     * @return $this
     */
    protected function _addServedModule(ModuleInterface $module)
    {
        $this->servedModules[$module->getKey()] = $module;

        return $this;
    }

    /**
     * Removes a module from the list of served modules.
     *
     * @since [*next-version*]
     *
     * @param string $key The module key.
     *
     * @return $this
     */
    protected function _removeServedModule($key)
    {
        unset($this->servedModules[$key]);

        return $this;
    }

    /**
     * Checks if a module is marked as already served.
     *
     * @since [*next-version*]
     *
     * @param string $key The module key.
     *
     * @return bool True if the module has already been served, false if not.
     */
    protected function _isModuleServed($key)
    {
        return isset($this->servedModules[$key]);
    }

    /**
     * Gets the current module being served.
     *
     * This method should be an inexpensive call to a cached result.
     *
     * @see AbstractModuleIterator::_determineCurrentModule()
     * @since [*next-version*]
     *
     * @return ModuleInterface|null The module instance or null if no module is being served.
     */
    protected function _getCurrent()
    {
        return $this->current;
    }

    /**
     * Sets the current module to serve.
     *
     * @since [*next-version*]
     *
     * @param ModuleInterface|null $current The module instance to serve. Default: null
     *
     * @return $this
     */
    protected function _setCurrent(ModuleInterface $current = null)
    {
        $this->current = $current;

        return $this;
    }

    /**
     * Retrieves the dependencies for a specific module.
     *
     * @since [*next-version*]
     *
     * @param ModuleInterface $module The module instance.
     *
     * @return ModuleInterface[]|ArrayAccess
     */
    abstract protected function _getModuleDependencies(ModuleInterface $module);

    /**
     * Gets the dependencies of a module that.
     *
     * @since [*next-version*]
     *
     * @param ModuleInterface $module The module instance.
     *
     * @return ModuleInterface[] A list of module instances mapped by their keys.
     */
    protected function _getUnservedModuleDependencies(ModuleInterface $module)
    {
        $_this        = $this;
        $dependencies = $this->_getModuleDependencies($module);

        return array_filter($dependencies, function ($dep) use ($_this) {
            return $dep instanceof ModuleInterface && !$_this->_isModuleServed($dep->getKey());
        });
    }

    /**
     * Resolves the actual module to load.
     *
     * Recursively retrieves the module's deep-most unserved dependency.
     *
     * Caters for circular dependency via the $ignore parameter. On every recursive call, the module
     * is recorded in the $ignore list so that it is ignored in subsequent recursive calls.
     *
     * This means that circular dependency in the form of "A requires B, B requires A" will result in
     * B be served prior to A. In other words, the first encountered module will have its dependency
     * loaded before it, even if that dependency requires the module.
     *
     * @since [*next-version*]
     *
     * @param ModuleInterface   $module The module instance.
     * @param ModuleInterface[] $ignore The module to ignore.
     *
     * @return ModuleInterface
     */
    protected function _getDeepMostUnservedModuleDependency(ModuleInterface $module, $ignore = array())
    {
        $moduleKey          = $module->getKey();
        $ignore[$moduleKey] = $module;
        $dependencies       = $this->_getUnservedModuleDependencies($module);
        $diffDependencies   = array_diff_key($dependencies, $ignore);

        // If there are no dependencies, return the given module
        if (empty($diffDependencies)) {
            return $module;
        }

        $dependency = array_shift($diffDependencies);

        return $this->_getDeepMostUnservedModuleDependency($dependency, $ignore);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     *
     * @return ModuleInterface
     */
    protected function _determineCurrentModule()
    {
        $module = parent::_current();

        return $module instanceof ModuleInterface
            ? $this->_getDeepMostUnservedModuleDependency($module)
            : null;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function _rewind()
    {
        parent::_rewind();

        $this->_setServedModules(array());
        $this->_setCurrent(null);
        $this->_next();
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function _current()
    {
        return $this->_getCurrent();
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function _next()
    {
        // Mark the previous module as served
        if (!is_null($previous = $this->_getCurrent())) {
            $this->_addServedModule($previous);
        }

        // Keep advancing until an unserved module is found or until end of module list
        while ($this->_valid() && $this->_isModuleServed(parent::_current()->getKey())) {
            parent::_next();
        }

        // Determine _actual_ current module, which may be a depedency of the found unserved module
        $this->_setCurrent($this->_determineCurrentModule());
    }
}