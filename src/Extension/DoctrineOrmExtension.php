<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Doctrine\Orm;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Rollerworks\Component\Search\AbstractExtension;

/**
 * Represents the doctrine ORM extension,
 * for the core Doctrine ORM functionality.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class DoctrineOrmExtension extends AbstractExtension
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry
     * @param array           $managerNames
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(ManagerRegistry $registry, $managerNames = array('default'))
    {
        foreach ((array) $managerNames as $managerName) {
            $manager = $registry->getManager($managerName);

            if (!$manager instanceof EntityManagerInterface) {
                throw new \InvalidArgumentException(
                    sprintf('Doctrine Manager "%s" is not an EntityManager.', $managerName)
                );
            }

            $emConfig = $manager->getConfiguration();
            /** @var \Doctrine\ORM\Configuration $emConfig */
            $emConfig->addCustomStringFunction(
                'RW_SEARCH_FIELD_CONVERSION',
                'Rollerworks\Component\Search\Doctrine\Orm\Functions\SqlFieldConversion'
            );

            $emConfig->addCustomStringFunction(
                'RW_SEARCH_VALUE_CONVERSION',
                'Rollerworks\Component\Search\Doctrine\Orm\Functions\SqlValueConversion'
            );

            $emConfig->addCustomStringFunction(
                'RW_SEARCH_MATCH',
                'Rollerworks\Component\Search\Doctrine\Orm\Functions\ValueMatch'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTypes()
    {
        return array(
            new Type\EntityCountType(),
        );
    }
}
