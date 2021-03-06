<?php

namespace WsdlToPhp\PackageGenerator\Container;

use WsdlToPhp\PackageGenerator\Generator\AbstractGeneratorAware;

abstract class AbstractObjectContainer extends AbstractGeneratorAware implements \ArrayAccess, \Iterator, \Countable, \JsonSerializable
{
    /**
     * @var string
     */
    const PROPERTY_NAME = 'name';
    /**
     * @var array
     */
    protected $objects = array();
    /**
     * @var int
     */
    protected $offset = 0;
    /**
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        $element = array_slice($this->objects, $offset, 1);
        return !empty($element);
    }
    /**
     * @param int $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $element = array_slice($this->objects, $offset, 1);
        return $this->offsetExists($offset) ? array_shift($element) : null;
    }
    /**
     * @param string $offset
     * @param mixed $value
     * @return AbstractObjectContainer
     */
    public function offsetSet($offset, $value)
    {
        throw new \InvalidArgumentException('This method can\'t be used as object are stored with a string as array index', __LINE__);
    }
    /**
     * @param string $offset
     * @return AbstractObjectContainer
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->objects[$this->getObjectKey($this->offsetGet($offset))]);
        }
        return $this;
    }
    /**
     * @return mixed
     */
    public function current()
    {
        $current = array_slice($this->objects, $this->offset, 1);
        return array_shift($current);
    }
    /**
     * @return void
     */
    public function next()
    {
        $this->offset++;
    }
    /**
     * @return int
     */
    public function key()
    {
        return $this->offset;
    }
    /**
     * @return bool
     */
    public function valid()
    {
        return count(array_slice($this->objects, $this->offset, 1)) > 0;
    }
    /**
     * @return AbstractObjectContainer
     */
    public function rewind()
    {
        $this->offset = 0;
        return $this;
    }
    /**
     * @return int
     */
    public function count()
    {
        return count($this->objects);
    }
    /**
     * Must return the object class name that this container is made to contain
     * @return string
     */
    abstract protected function objectClass();
    /**
     * Must return the object class name that this container is made to contain
     * @return string
     */
    abstract protected function objectProperty();
    /**
     * This method is called before the object has been stored
     * @throws \InvalidArgumentException
     * @param mixed $object
     */
    protected function beforeObjectIsStored($object)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException(sprintf('You must only pass object to this container (%s), "%s" passed as parameter!', get_called_class(), gettype($object)), __LINE__);
        }
        $instanceOf = $this->objectClass();
        if (get_class($object) !== $this->objectClass() && !$object instanceof $instanceOf) {
            throw new \InvalidArgumentException(sprintf('Model of type "%s" does not match the object contained by this class: "%s"', get_class($object), $this->objectClass()), __LINE__);
        }
    }
    /**
     * @param object $object
     * @throws \InvalidArgumentException
     * @return string
     */
    private function getObjectKey($object)
    {
        $get = sprintf('get%s', ucfirst($this->objectProperty()));
        if (!method_exists($object, $get)) {
            throw new \InvalidArgumentException(sprintf('Method "%s" is required in "%s" in order to be stored in "%s"', $get, get_class($object), get_class($this)), __LINE__);
        }
        $key = $object->$get();
        if (!is_scalar($key)) {
            throw new \InvalidArgumentException(sprintf('Property "%s" of "%s" must be scalar, "%s" returned', $this->objectProperty(), get_class($object), gettype($key)), __LINE__);
        }
        return $key;
    }
    /**
     * @throws \InvalidArgumentException
     * @param mixed $object
     * @return AbstractObjectContainer
     */
    public function add($object)
    {
        $this->beforeObjectIsStored($object);
        $this->objects[$this->getObjectKey($object)] = $object;
        return $this;
    }
    /**
     * @param string $value
     * @return mixed
     */
    public function get($value)
    {
        if (!is_string($value) && !is_int($value)) {
            throw new \InvalidArgumentException(sprintf('Value "%s" can\'t be used to get an object from "%s"', var_export($value, true), get_class($this)), __LINE__);
        }
        return array_key_exists($value, $this->objects) ? $this->objects[$value] : null;
    }
    public function jsonSerialize()
    {
        return array_values($this->objects);
    }
}
