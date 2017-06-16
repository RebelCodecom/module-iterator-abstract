<?php

namespace RebelCode\Modular\Iterator;

use Dhii\Modular\Module\ModuleInterface;

/**
 * Basic functionality for a module iterator.
 *
 * @since [*next-version*]
 */
abstract class AbstractModuleIterator
{
    /**
     * The modules to be iterated.
     *
     * @since [*next-version*]
     *
     * @var ModuleInterface[]
     */
    protected $modules;

    /**
     * A map of the module instances mapped using the module keys.
     *
     * @since [*next-version*]
     *
     * @var ModuleInterface[]
     */
    protected $moduleMap;

    /**
     * The numeric index of the current module being served.
     *
     * @since [*next-version*]
     *
     * @var int
     */
    protected $index;

    /**
     * Retrieves the modules to be iterated.
     *
     * @since [*next-version*]
     *
     * @return ModuleInterface[] An array of modules.
     */
    protected function _getModules()
    {
        return $this->modules;
    }

    /**
     * Sets the modules to be served.
     *
     * @since [*next-version*]
     *
     * @param ModuleInterface[] $modules The module instances
     *
     * @return $this
     */
    protected function _setModules(array $modules)
    {
        $this->modules   = array_values($modules);
        $this->moduleMap = $this->_createModuleMap($this->modules);

        return $this;
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
        return isset($this->moduleMap[$key])
            ? $this->moduleMap[$key]
            : null;
    }

    /**
     * Gets the numeric index of the current module being served.
     *
     * @since [*next-version*]
     *
     * @return int
     */
    protected function _getIndex()
    {
        return $this->index;
    }

    /**
     * Sets the numeric index of the current module to serve.
     *
     * @since [*next-version*]
     *
     * @param int $index A zero-based index integer.
     *
     * @return $this
     */
    protected function _setIndex($index)
    {
        $this->index = $index;

        return $this;
    }

    /**
     * Retrieves the module at a specific numeric index.
     *
     * @since [*next-version*]
     *
     * @param int $index The numeric, zero-based index.
     *
     * @return ModuleInterface|null The module instance or null if the index is invalid.
     */
    protected function _getModuleAtIndex($index)
    {
        $modules = $this->_getModules();

        return isset($modules[$index])
            ? $modules[$index]
            : null;
    }

    /**
     * Retrieves the module at the current index.
     *
     * @since [*next-version*]
     *
     * @return ModuleInterface|null The module instance or null if the current index is invalid.
     */
    protected function _getModuleAtCurrentIndex()
    {
        return $this->_getModuleAtIndex($this->_getIndex());
    }

    /**
     * Determines which module should currently be served.
     *
     * @since [*next-version*]
     *
     * @return ModuleInterface|null The module instance or null on failure to determine the module.
     */
    protected function _determineCurrentModule()
    {
        return $this->_getModuleAtCurrentIndex();
    }

    /**
     * Rewinds the iterator to the first element.
     *
     * @since [*next-version*]
     */
    protected function _rewind()
    {
        $this->_setIndex(0);
    }

    /**
     * Serves the current element.
     *
     * @since [*next-version*]
     *
     * @return ModuleInterface
     */
    protected function _current()
    {
        return $this->_determineCurrentModule();
    }

    /**
     * Gets the key of the current element.
     *
     * @since [*next-version*]
     *
     * @return string
     */
    protected function _key()
    {
        $current = $this->_current();

        return is_null($current)
            ? null
            : $current->getKey();
    }

    /**
     * Moves the iterator to the next element.
     *
     * @since [*next-version*]
     */
    protected function _next()
    {
        $this->_setIndex($this->_getIndex() + 1);
    }

    /**
     * Checks if the current element is valid.
     *
     * @since [*next-version*]
     *
     * @return bool True if the element is valid, false if not.
     */
    protected function _valid()
    {
        return $this->_getModuleAtCurrentIndex() !== null;
    }
}