<?php namespace DCarbone;

/*
    Easier to use PHP XMLWriter implementation
    Copyright (C) 2012-2015  Daniel Paul Carbone

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

use \XMLWriter;

/**
 * Class XMLWriterPlus
 * @package DCarbone\Helpers
 */
class XMLWriterPlus extends \XMLWriter
{
    /** @var string */
    protected $encoding;

    /** @var array */
    protected $nsArray = array();

    /** @var bool */
    protected $memory = false;

    /**
     * Destructor
     *
     * @link http://www.php.net/manual/en/function.xmlwriter-flush.php
     */
    public function __destruct()
    {
        $this->flush();
    }

    /**
     * @param string $prefix
     * @param string $uri
     */
    public function addNS($prefix, $uri)
    {
        $this->nsArray[$prefix] = $uri;
    }

    /**
     * @param string $prefix
     */
    public function removeNS($prefix)
    {
        if (isset($this->nsArray[$prefix]) || array_key_exists($prefix, $this->nsArray))
            unset($this->nsArray[$prefix]);
    }

    /**
     * @param string $prefix
     * @return bool
     */
    public function hasNSPrefix($prefix)
    {
        return isset($this->nsArray[$prefix]) || array_key_exists($prefix, $this->nsArray);
    }

    /**
     * @param string $uri
     * @return bool
     */
    public function hasNSUri($uri)
    {
        return in_array($uri, $this->nsArray, true);
    }

    /**
     * @return array
     */
    public function getNSArray()
    {
        return $this->nsArray;
    }

    /**
     * @param array $nsArray
     */
    public function setNSArray(array $nsArray)
    {
        $this->nsArray = $nsArray;
    }

    /**
     * @param string $prefix
     * @return string|bool
     */
    public function getNSUriFromPrefix($prefix)
    {
        if ($this->hasNSPrefix($prefix))
            return $this->nsArray[$prefix];

        return false;
    }

    /**
     * @param string $uri
     * @return mixed
     */
    public function getNSPrefixFromUri($uri)
    {
        return array_search($uri, $this->nsArray, true);
    }

    /**
     * @return bool
     */
    public function openMemory()
    {
        $this->memory = true;
        return parent::openMemory();
    }

    /**
     * @param string $uri
     * @return bool
     */
    public function openUri($uri)
    {
        $this->memory = false;
        return parent::openUri($uri);
    }

    /**
     * @param float $version
     * @param string $encoding
     * @param bool $standalone
     * @return bool|void
     */
    public function startDocument($version = 1.0, $encoding = 'UTF-8', $standalone = null)
    {
        if (is_float($version) || is_int($version))
            $version = number_format((float)$version, 1);

        $this->encoding = $encoding;
        parent::startDocument($version, $encoding, $standalone);
    }

    /**
     * @param string $prefix
     * @param string $name
     * @param string $uri
     * @return bool
     */
    public function startAttributeNS($prefix, $name, $uri = null)
    {
        $this->nsArray[$prefix] = $uri;
        return parent::startAttributeNS($prefix, $name, $uri);
    }

    /**
     * @param string $prefix
     * @param string $name
     * @param string $uri
     * @param string $content
     * @return bool
     */
    public function writeAttributeNS($prefix, $name, $uri = null, $content)
    {
        if (!$this->hasNSPrefix($prefix))
            $this->nsArray[$prefix] = $uri;

        return parent::writeAttributeNS($prefix, $name, $uri, $content);
    }

    /**
     * @param string $prefix
     * @param string $name
     * @param string $uri
     * @return bool
     */
    public function startElementNS($prefix, $name, $uri = null)
    {
        if (!$this->hasNSPrefix($prefix))
            $this->nsArray[$prefix] = $uri;

        return parent::startElementNS($prefix, $name, $uri);
    }

    /**
     * @param string $prefix
     * @param string $name
     * @param string $uri
     * @param null|string $content
     * @return bool
     */
    public function writeElementNS($prefix, $name, $uri = null, $content = null)
    {
        if (!$this->hasNSPrefix($prefix))
            $this->nsArray[$prefix] = $uri;

        return parent::writeElementNS($prefix, $name, $uri, $content);
    }

    /**
     * @param string $name
     * @param string|null $nsPrefix
     * @return bool
     */
    public function startElement($name, $nsPrefix = null)
    {
        if ($nsPrefix === null)
            return parent::startElement($name);

        return $this->startElementNS($nsPrefix, $name);
    }

    /**
     * Write Text into Attribute or Element
     *
     * @link  http://www.php.net/manual/en/function.xmlwriter-text.php
     *
     * @param string $text Value to write
     * @throws \InvalidArgumentException
     * @return  bool
     */
    public function text($text)
    {
        if (is_string($text) || settype($text, 'string' ) !== false)
        {
            $encoded = $this->encodeString($text);
            return parent::text($encoded);
        }

        throw new \InvalidArgumentException(get_class($this).':text - Cannot cast passed value to string (did you forget to define a __toString on your object?)');
    }

    /**
     * @param string $name
     * @param string|null $content
     * @param string|null $nsPrefix
     * @return bool
     */
    public function writeElement($name, $content = null, $nsPrefix = null)
    {
        if ($nsPrefix === null)
        {
            return ($this->startElement($name) &&
                $this->text($content) &&
                $this->endElement(($content === null ? true : false)));
        }

        if ($this->hasNSPrefix($nsPrefix))
        {
            return $this->writeElementNS(
                $nsPrefix,
                $name,
                $this->getNSUriFromPrefix($nsPrefix),
                $content);
        }

        return $this->writeElementNS($nsPrefix, $name, null, $content);
    }

    /**
     * Write ending element
     *
     * @link  http://www.php.net/manual/en/function.xmlwriter-full-end-element.php
     *
     * @param bool $full
     * @return bool
     */
    public function endElement($full = false)
    {
        if ($full === true)
            return $this->fullEndElement();

        return parent::endElement();
    }

    /**
     * @param string $name
     * @param string $content
     * @param string|null $nsPrefix
     * @return bool
     */
    public function writeCDataElement($name, $content, $nsPrefix = null)
    {
        if ($nsPrefix === null)
        {
            return ($this->startElement($name) &&
                $this->writeCdata($content) &&
                $this->endElement(true));
        }

        if ($this->hasNSPrefix($nsPrefix))
        {
            return ($this->startElementNS($nsPrefix, $name, $this->getNSUriFromPrefix($nsPrefix)) &&
                $this->writeCdata($content) &&
                $this->endElement(true));
        }

        return ($this->writeElementNS($nsPrefix, $name, null) &&
            $this->writeCdata($content) &&
            $this->endElement(true));
    }

    /**
     * Append an integer index array of values to this XML document
     *
     * @param array $data
     * @param string $elementName
     * @param null|string $nsPrefix
     * @return bool
     */
    public function appendList(array $data, $elementName, $nsPrefix = null)
    {
        foreach($data as $value)
        {
            $this->writeElement($elementName, $value, $nsPrefix);
        }

        return true;
    }

    /**
     * Append an associative array or object to this XML document
     *
     * @param array|object $data
     * @param string|null $_previousKey
     * @return bool
     */
    public function appendHash($data, $_previousKey = null)
    {
        foreach($data as $key=>$value)
        {
            $this->appendHashData($key, $value, $_previousKey);
        }

        return false;
    }

    /**
     * @param string|int $key
     * @param mixed $value
     * @param null|string $_previousKey
     */
    protected function appendHashData($key, $value, $_previousKey)
    {
        if (is_scalar($value))
        {
            if (is_string($key) && false === ctype_digit($key))
            {
                if (false === strpos($key, ':'))
                {
                    $this->writeElement($key, $value);
                }
                else
                {
                    $exp = explode(':', $key);
                    $this->writeElement($exp[1], $value, $exp[0]);
                }
            }
            else if (is_numeric($key) && $_previousKey !== null && !is_numeric($_previousKey))
            {
                $this->writeElement($_previousKey, $value);
            }
            else
            {
                $this->writeElement($key, $value);
            }

            return;
        }

        if (is_numeric($key))
        {
            foreach($value as $k=>$v)
            {
                $this->appendHashData($k, $v, $_previousKey);
            }
        }
        else if (strstr($key, ':') !== false)
        {
            $exp = explode(':', $key);
            $this->startElementNS($exp[0], $exp[1]);
            $this->appendHash($value, $key);
            $this->endElement(true);
        }
        else
        {
            $this->startElement($key);
            $this->appendHash($value, $key);
            $this->endElement(true);
        }
    }

    /**
     * Apply requested encoding type to string
     *
     * @link  http://php.net/manual/en/function.mb-detect-encoding.php
     * @link  http://www.php.net/manual/en/function.mb-convert-encoding.php
     *
     * @param   string  $string  un-encoded string
     * @return  string
     */
    protected function encodeString($string)
    {
        // If no encoding value was passed in...
        if ($this->encoding === null)
            return $string;

        $detect = mb_detect_encoding($string);

        // If the current encoding is already the requested encoding
        if (is_string($detect) && $detect === $this->encoding)
            return $string;

        // Failed to determine encoding
        if (is_bool($detect))
            return $string;

        // Convert it!
        return mb_convert_encoding($string, $this->encoding, $detect);
    }

    /**
     * @param bool $flush
     * @param bool $endDocument
     * @param null|int $sxeArgs
     * @return null|\SimpleXMLElement
     * @throws \Exception
     */
    public function getSXEFromMemory($flush = false, $endDocument = false, $sxeArgs = null)
    {
        if ($this->memory === true)
        {
            if ($endDocument === true)
                $this->endDocument();

            try {
                if (null === $sxeArgs)
                {
                    if (defined('LIBXML_PARSEHUGE'))
                        $sxeArgs = LIBXML_COMPACT | LIBXML_PARSEHUGE;
                    else
                        $sxeArgs = LIBXML_COMPACT;
                }

                return new \SimpleXMLElement($this->outputMemory((bool)$flush), $sxeArgs);
            }
            catch (\Exception $e) {
                if (libxml_get_last_error() !== false)
                    throw new \Exception(get_class($this).'::getSXEFromMemory - Error encountered: "'.libxml_get_last_error()->message.'"');
                else
                    throw new \Exception(get_class($this).'::getSXEFromMemory - Error encountered: "'.$e->getMessage().'"');
            }
        }

        return null;
    }

    /**
     * @param bool $flush
     * @param bool $endDocument
     * @param float $version
     * @param string $encoding
     * @return \DOMDocument|null
     * @throws \Exception
     */
    public function getDOMFromMemory($flush = false, $endDocument = false, $version = 1.0, $encoding = 'UTF-8')
    {
        if ($this->memory === true)
        {
            if ($endDocument === true)
                $this->endDocument();

            try {
                $dom = new \DOMDocument($version, $encoding);
                $dom->loadXML($this->outputMemory((bool)$flush));

                return $dom;
            }
            catch (\Exception $e) {
                if (libxml_get_last_error() !== false)
                    throw new \Exception(get_class($this).'::getDOMFromMemory - Error encountered: "'.libxml_get_last_error()->message.'"');
                else
                    throw new \Exception(get_class($this).'::getDOMFromMemory - Error encountered: "'.$e->getMessage().'"');
            }
        }

        return null;
    }
}