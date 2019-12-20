<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\apidoc\models;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tag;
use phpDocumentor\Reflection\Element;
use yii\base\BaseObject;

/**
 * Base class for API documentation information.
 *
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
class BaseDoc extends BaseObject
{
    /**
     * @var \phpDocumentor\Reflection\Types\Context
     */
    public $phpDocContext;
    public $name;
    public $fqsen;
    public $sourceFile;
    public $startLine;
    public $endLine;
    public $shortDescription;
    public $description;
    public $since;
    public $deprecatedSince;
    public $deprecatedReason;
    /**
     * @var Tag[]
     */
    public $tags = [];


    /**
     * Checks if doc has tag of a given name
     * @param string $name tag name
     * @return bool if doc has tag of a given name
     */
    public function hasTag($name)
    {
        foreach ($this->tags as $tag) {
            if (strtolower($tag->getName()) == $name) {
                return true;
            }
        }
        return false;
    }

    /**
     * Removes tag of a given name
     * @param string $name
     */
    public function removeTag($name)
    {
        foreach ($this->tags as $i => $tag) {
            if (strtolower($tag->getName()) == $name) {
                unset($this->tags[$i]);
            }
        }
    }

    /**
     * Get the first tag of a given name
     * @param string $name tag name.
     * @return Tag|null tag instance, `null` if not found.
     * @since 2.0.5
     */
    public function getFirstTag($name)
    {
        foreach ($this->tags as $i => $tag) {
            if (strtolower($tag->getName()) == $name) {
                return $this->tags[$i];
            }
        }

        return null;
    }

    /**
     * @param Element $reflector
     * @param Context $context
     * @param array $config
     */
    public function __construct($reflector = null, $context = null, $config = [])
    {
        parent::__construct($config);

        if ($reflector === null) {
            return;
        }

        // base properties
        $this->name = ltrim($reflector->getName(), '\\');
        $this->startLine = -1; // $reflector->getNode()->getAttribute('startLine');
        $this->endLine = -1; // $reflector->getNode()->getAttribute('endLine');

        /** @var DocBlock $docblock */
        $docblock = $reflector->getDocBlock();
        if ($docblock !== null) {
            /*$this->shortDescription = static::mbUcFirst($docblock->getShortDescription());
            if (empty($this->shortDescription) && !($this instanceof PropertyDoc) && $context !== null && $docblock->getTagsByName('inheritdoc') === null) {
                $context->warnings[] = [
                    'line' => $this->startLine,
                    'file' => $this->sourceFile,
                    'message' => "No short description for " . substr(StringHelper::basename(get_class($this)), 0, -3) . " '{$this->name}'",
                ];
            }*/
            $this->description = $docblock->getDescription()->render();

            $this->phpDocContext = $docblock->getContext();

            $this->tags = $docblock->getTags();
            foreach ($this->tags as $i => $tag) {
                if ($tag instanceof DocBlock\Tags\Since) {
                    $this->since = $tag->getVersion();
                    unset($this->tags[$i]);
                } elseif ($tag instanceof DocBlock\Tags\Deprecated) {
                    $this->deprecatedSince = $tag->getVersion();
                    $this->deprecatedReason = $tag->getDescription();
                    unset($this->tags[$i]);
                }
            }

            /*if ($this->shortDescription === '{@inheritdoc}') {
                // Mock up parsing of '{@inheritdoc}' (in brackets) tag, which is not yet supported at "phpdocumentor/reflection-docblock" 2.x
                // todo consider removal in case of "phpdocumentor/reflection-docblock" upgrade
                $this->tags[] = new Tag('inheritdoc', '');
                $this->shortDescription = '';
            }*/

        } elseif ($context !== null) {
            $context->warnings[] = [
                'line' => $this->startLine,
                'file' => $this->sourceFile,
                'message' => "No docblock for element '{$this->name}'",
            ];
        }
    }

    /**
     * Extracts first sentence out of text
     * @param string $text
     * @return string
     */
    public static function extractFirstSentence($text)
    {
        if (mb_strlen($text, 'utf-8') > 4 && ($pos = mb_strpos($text, '.', 4, 'utf-8')) !== false) {
            $sentence = mb_substr($text, 0, $pos + 1, 'utf-8');
            if (mb_strlen($text, 'utf-8') >= $pos + 3) {
                $abbrev = mb_substr($text, $pos - 1, 4, 'utf-8');
                if ($abbrev === 'e.g.' || $abbrev === 'i.e.') { // do not break sentence after abbreviation
                    $sentence .= static::extractFirstSentence(mb_substr($text, $pos + 1, mb_strlen($text, 'utf-8'), 'utf-8'));
                }
            }
            return $sentence;
        }

        return $text;
    }

    /**
     * Multibyte version of ucfirst()
     * @param $string
     * @return string
     * @since 2.0.6
     */
    protected static function mbUcFirst($string)
    {
        $firstChar = mb_strtoupper(mb_substr($string, 0, 1, 'utf-8'), 'utf-8');
        return $firstChar . mb_substr($string, 1, mb_strlen($string, 'utf-8'), 'utf-8');
    }
}
