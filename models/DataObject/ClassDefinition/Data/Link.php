<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Tool\Serialize;

class Link extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface
{
    use Extension\ColumnType;
    use Extension\QueryColumnType;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'link';

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = 'text';

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = 'text';

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = '\\Pimcore\\Model\\DataObject\\Data\\Link';

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param DataObject\Data\Link $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data instanceof DataObject\Data\Link) {
            $data = clone $data;
            $data->setOwner(null, '');

            if ($data->getLinktype() == 'internal' && !$data->getPath()) {
                $data->setLinktype(null);
                $data->setInternalType(null);
                if ($data->isEmpty()) {
                    return null;
                }
            }

            try {
                $this->checkValidity($data, true);
            } catch (\Exception $e) {
                $data->setInternalType(null);
                $data->setInternal(null);
            }
        }

        if (is_null($data)) {
            return null;
        }

        return Serialize::serialize($data);
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return DataObject\Data\Link
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        $link = Serialize::unserialize($data);

        if ($link instanceof DataObject\Data\Link) {
            if (isset($params['owner'])) {
                $link->setOwner($params['owner'], $params['fieldname'], $params['language']);
            }

            try {
                $this->checkValidity($link, true);
            } catch (\Exception $e) {
                $link->setInternalType(null);
                $link->setInternal(null);
            }
        }

        return $link;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param DataObject\Data\Link $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if (!$data instanceof DataObject\Data\Link) {
            return null;
        }
        $data->path = $data->getPath();

        return $data->getObjectVars();
    }

    /**
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return DataObject\Data\Link|null
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        $link = new DataObject\Data\Link();
        $link->setValues($data);

        if ($link->isEmpty()) {
            return null;
        }

        return $link;
    }

    /**
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param string $data
     * @param null|DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param bool $omitMandatoryCheck
     *
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {
        if ($data) {
            if ($data instanceof DataObject\Data\Link) {
                if (intval($data->getInternal()) > 0) {
                    if ($data->getInternalType() == 'document') {
                        $doc = Document::getById($data->getInternal());
                        if (!$doc instanceof Document) {
                            throw new Element\ValidationException('invalid internal link, referenced document with id [' . $data->getInternal() . '] does not exist');
                        }
                    } elseif ($data->getInternalType() == 'asset') {
                        $asset = Asset::getById($data->getInternal());
                        if (!$asset instanceof Asset) {
                            throw new Element\ValidationException('invalid internal link, referenced asset with id [' . $data->getInternal() . '] does not exist');
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $data
     *
     * @return array
     */
    public function resolveDependencies($data)
    {
        $dependencies = [];

        if ($data instanceof DataObject\Data\Link and $data->getInternal()) {
            if (intval($data->getInternal()) > 0) {
                if ($data->getInternalType() == 'document') {
                    if ($doc = Document::getById($data->getInternal())) {
                        $key = 'document_' . $doc->getId();
                        $dependencies[$key] = [
                            'id' => $doc->getId(),
                            'type' => 'document'
                        ];
                    }
                } elseif ($data->getInternalType() == 'asset') {
                    if ($asset = Asset::getById($data->getInternal())) {
                        $key = 'asset_' . $asset->getId();

                        $dependencies[$key] = [
                            'id' => $asset->getId(),
                            'type' => 'asset'
                        ];
                    }
                }
            }
        }

        return $dependencies;
    }

    /**
     * This is a dummy and is mostly implemented by relation types
     *
     * @param mixed $data
     * @param array $tags
     *
     * @return array
     */
    public function getCacheTags($data, $tags = [])
    {
        $tags = is_array($tags) ? $tags : [];

        if ($data instanceof DataObject\Data\Link and $data->getInternal()) {
            if (intval($data->getInternal()) > 0) {
                if ($data->getInternalType() == 'document') {
                    if ($doc = Document::getById($data->getInternal())) {
                        if (!array_key_exists($doc->getCacheTag(), $tags)) {
                            $tags = $doc->getCacheTags($tags);
                        }
                    }
                } elseif ($data->getInternalType() == 'asset') {
                    if ($asset = Asset::getById($data->getInternal())) {
                        if (!array_key_exists($asset->getCacheTag(), $tags)) {
                            $tags = $asset->getCacheTags($tags);
                        }
                    }
                }
            }
        }

        return $tags;
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param DataObject\AbstractObject $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof DataObject\Data\Link) {
            return base64_encode(Serialize::serialize($data));
        } else {
            return null;
        }
    }

    /**
     * fills object field data values from CSV Import String
     *
     * @param string $importValue
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return DataObject\ClassDefinition\Data\Link
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $value = Serialize::unserialize(base64_decode($importValue));
        if ($value instanceof DataObject\Data\Link) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * @param $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof DataObject\Data\Link) {
            return $data->getText();
        }

        return '';
    }

    /**
     * converts data to be exposed via webservices
     *
     * @param string $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getForWebserviceExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof DataObject\Data\Link) {
            $keys = $data->getObjectVars();
            foreach ($keys as $key => $value) {
                $method = 'get' . ucfirst($key);
                if (!method_exists($data, $method) or $key == 'object') {
                    unset($keys[$key]);
                }
            }

            return $keys;
        } else {
            return null;
        }
    }

    /**
     * @param mixed $value
     * @param null $relatedObject
     * @param mixed $params
     * @param null $idMapper
     *
     * @return mixed|void
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $relatedObject = null, $params = [], $idMapper = null)
    {
        if ($value instanceof \stdclass) {
            $value = (array) $value;
        }

        if (empty($value)) {
            return null;
        } elseif (is_array($value) and !empty($value['text']) and !empty($value['direct'])) {
            $link = new DataObject\Data\Link();
            foreach ($value as $key => $v) {
                $method = 'set' . ucfirst($key);
                if (method_exists($link, $method)) {
                    $link->$method($v);
                } else {
                    throw new \Exception('cannot get values from web service import - invalid data. Unknown DataObject\\Data\\Link setter [ ' . $method . ' ]');
                }
            }

            return $link;
        } elseif (is_array($value) and !empty($value['text']) and !empty($value['internalType']) and !empty($value['internal'])) {
            $id = $value['internal'];

            if ($idMapper) {
                $id = $idMapper->getMappedId($value['internalType'], $id);
            }

            $element = Element\Service::getElementById($value['internalType'], $id);
            if (!$element) {
                if ($idMapper && $idMapper->ignoreMappingFailures()) {
                    $idMapper->recordMappingFailure('object', $relatedObject->getId(), $value['internalType'], $value['internal']);

                    return null;
                } else {
                    throw new \Exception('cannot get values from web service import - referencing unknown internal element with type [ '.$value['internalType'].' ] and id [ '.$value['internal'].' ]');
                }
            }

            $link = new DataObject\Data\Link();
            foreach ($value as $key => $v) {
                $method = 'set' . ucfirst($key);
                if (method_exists($link, $method)) {
                    $link->$method($v);
                } else {
                    throw new \Exception('cannot get values from web service import - invalid data. Unknown DataObject\\Data\\Link setter [ ' . $method . ' ]');
                }
            }

            return $link;
        } elseif (is_array($value)) {
            $link = new DataObject\Data\Link();
            foreach ($value as $key => $v) {
                $method = 'set' . ucfirst($key);
                if (method_exists($link, $method)) {
                    $link->$method($v);
                } else {
                    throw new \Exception('cannot get values from web service import - invalid data. Unknown DataObject\\Data\\Link setter [ ' . $method . ' ]');
                }
            }

            return $link;
        } else {
            throw new \Exception('cannot get values from web service import - invalid data');
        }
    }

    /** True if change is allowed in edit mode.
     * @param string $object
     * @param mixed $params
     *
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the ObjectMerger plugin documentation for details
     *
     * @param $data
     * @param null $object
     * @param mixed $params
     *
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null, $params = [])
    {
        if ($data) {
            if ($data->text) {
                return $data->text;
            } elseif ($data->direct) {
                return $data->direct;
            }
        }
    }

    /**
     * Rewrites id from source to target, $idMapping contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     *
     * @param mixed $object
     * @param array $idMapping
     * @param array $params
     *
     * @return Element\ElementInterface
     */
    public function rewriteIds($object, $idMapping, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof DataObject\Data\Link && $data->getLinktype() == 'internal') {
            $id = $data->getInternal();
            $type = $data->getInternalType();

            if (array_key_exists($type, $idMapping) and array_key_exists($id, $idMapping[$type])) {
                $data->setInternal($idMapping[$type][$id]);
            }
        }

        return $data;
    }
}