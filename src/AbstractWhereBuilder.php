<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Orm;

use Doctrine\DBAL\Types\Type as MappingType;
use Doctrine\ORM\EntityManagerInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversionInterface;
use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\Exception\UnknownFieldException;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchConditionInterface;

/**
 * Handles abstracted handling of the Doctrine WhereBuilder.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractWhereBuilder
{
    use QueryPlatformTrait;

    /**
     * @var SearchConditionInterface
     */
    protected $searchCondition;

    /**
     * @var FieldSet
     */
    protected $fieldset;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var string
     */
    protected $whereClause;

    /**
     * @var FieldConfigBuilder
     */
    protected $fieldsConfig;

    /**
     * Constructor.
     *
     * @param SearchConditionInterface $searchCondition SearchCondition object
     * @param EntityManagerInterface   $entityManager
     */
    public function __construct(SearchConditionInterface $searchCondition, EntityManagerInterface $entityManager)
    {
        if ($searchCondition->getValuesGroup()->hasErrors(true)) {
            throw new BadMethodCallException(
                'Unable to generate the where-clause, because the SearchCondition contains errors.'
            );
        }

        $this->searchCondition = $searchCondition;
        $this->fieldset = $searchCondition->getFieldSet();

        $this->entityManager = $entityManager;
        $this->fieldsConfig = new FieldConfigBuilder($entityManager, $this->fieldset);
    }

    /**
     * Set the entity mapping per class.
     *
     * @param string $entityName class or Doctrine alias
     * @param string $alias      Entity alias as used in the query.
     *                           Set to the null to remove the mapping
     *
     * @throws BadMethodCallException When the where-clause is already generated.
     *
     * @return self
     */
    public function setEntityMapping($entityName, $alias)
    {
        $this->guardNotGenerated();
        $this->fieldsConfig->setEntityMapping($entityName, $alias);

        return $this;
    }

    /**
     * Set the entity mappings.
     *
     * Mapping is set as [class] => in-query-entity-alias.
     *
     * Caution. This will overwrite any configured entity-mappings.
     *
     * @param array $mapping
     *
     * @throws BadMethodCallException When the where-clause is already generated.
     *
     * @return self
     */
    public function setEntityMappings(array $mapping)
    {
        $this->guardNotGenerated();
        $this->fieldsConfig->setEntityMappings($mapping);

        return $this;
    }

    /**
     * Set Field configuration for the query-generation.
     *
     * Note: The property must be owned by the entity (not reference another entity).
     * If the entity field is used in a many-to-many relation you must to reference the
     * targetEntity that is set on the ManyToMany mapping and use the entity field of that entity.
     *
     * @param string             $fieldName   Name of the Search field
     * @param string             $alias       Entity alias as used in the query
     * @param string             $entity      Entity name (FQCN or Doctrine aliased)
     * @param string             $property    Entity field name
     * @param string|MappingType $mappingType Doctrine Mapping-type
     *
     * @throws UnknownFieldException  When the field is not registered in the fieldset.
     * @throws BadMethodCallException When the where-clause is already generated.
     *
     * @return self
     */
    public function setField($fieldName, $alias, $entity = null, $property = null, $mappingType = null)
    {
        $this->guardNotGenerated();
        $this->fieldsConfig->setField($fieldName, $alias, $entity, $property, $mappingType);

        return $this;
    }

    /**
     * Set the converters for a field.
     *
     * Setting is done per type (field or value), any existing conversions are overwritten.
     *
     * @param string                                               $fieldName
     * @param ValueConversionInterface|SqlFieldConversionInterface $converter
     *
     * @throws UnknownFieldException  When the field is not registered in the fieldset.
     * @throws BadMethodCallException When the where-clause is already generated.
     *
     * @return self
     */
    public function setConverter($fieldName, $converter)
    {
        $this->guardNotGenerated();
        $this->fieldsConfig->setConverter($fieldName, $converter);

        return $this;
    }

    /**
     * @return SearchConditionInterface
     */
    public function getSearchCondition()
    {
        return $this->searchCondition;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @return FieldConfigBuilder
     *
     * @internal
     */
    public function getFieldsConfig()
    {
        return $this->fieldsConfig;
    }

    /**
     * @throws BadMethodCallException When the where-clause is already generated.
     */
    protected function guardNotGenerated()
    {
        if (null !== $this->whereClause) {
            throw new BadMethodCallException(
                'WhereBuilder configuration methods cannot be accessed anymore once the where-clause is generated.'
            );
        }
    }
}