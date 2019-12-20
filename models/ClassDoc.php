<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\apidoc\models;

use phpDocumentor\Reflection\Php\Class_;

/**
 * Represents API documentation information for a `class`.
 *
 * @property EventDoc[] $nativeEvents This property is read-only.
 *
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
class ClassDoc extends TypeDoc
{
    /**
     * @var string
     */
    public $parentClass;
    /**
     * @var bool
     */
    public $isAbstract;
    /**
     * @var bool
     */
    public $isFinal;
    /**
     * @var string[]
     */
    public $interfaces = [];
    /**
     * @var string[]
     */
    public $traits = [];
    // will be set by Context::updateReferences()
    /**
     * @var string[]
     */
    public $subclasses = [];
    /**
     * @var EventDoc[]
     */
    public $events = [];
    /**
     * @var ConstDoc[]
     */
    public $constants = [];


    /**
     * @inheritdoc
     */
    public function findSubject($subjectName)
    {
        if (($subject = parent::findSubject($subjectName)) !== null) {
            return $subject;
        }
        foreach ($this->events as $name => $event) {
            if ($subjectName == $name) {
                return $event;
            }
        }
        foreach ($this->constants as $name => $constant) {
            if ($subjectName == $name) {
                return $constant;
            }
        }

        return null;
    }

    /**
     * @return EventDoc[]
     */
    public function getNativeEvents()
    {
        $events = [];
        foreach ($this->events as $name => $event) {
            if ($event->definedBy != $this->name) {
                continue;
            }
            $events[$name] = $event;
        }

        return $events;
    }

    /**
     * @param Class_ $reflector
     * @param null $context
     * @param array $config
     */
    public function __construct($reflector = null, $context = null, $config = [])
    {
        parent::__construct($reflector, $context, $config);

        if ($reflector === null) {
            return;
        }

        $this->parentClass = ltrim($reflector->getParent(), '\\');
        $this->fqsen = ltrim((string) $reflector->getFqsen(), '\\');
        if (empty($this->parentClass)) {
            $this->parentClass = null;
        }
        $this->isAbstract = $reflector->isAbstract();
        $this->isFinal = $reflector->isFinal();

        foreach ($reflector->getInterfaces() as $interface) {
            $this->interfaces[] = ltrim($interface, '\\');
        }
        foreach ($reflector->getUsedTraits() as $trait) {
            $this->traits[] = ltrim($trait, '\\');
        }
        foreach ($reflector->getConstants() as $constantReflector) {
            $docblock = $constantReflector->getDocBlock();
            if ($docblock !== null && count($docblock->getTagsByName('event')) > 0) {
                $event = new EventDoc($constantReflector);
                $event->definedBy = $this->name;
                $this->events[$event->name] = $event;
            } else {
                $constant = new ConstDoc($constantReflector);
                $constant->definedBy = $this->name;
                $this->constants[$constant->name] = $constant;
            }
        }
    }
}
