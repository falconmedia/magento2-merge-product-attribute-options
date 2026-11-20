<?php

namespace FalconMedia\MergeAttributeOptions\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Renames the label of an attribute option for one store view.
 */
class RenameStoreOption extends AbstractRenameOption
{
    const STORE_ID = 'store_id';

    protected function configure(): void
    {
        $this->setName('falconmedia:attribute:rename-store-option')
            ->setDescription('Rename the label of an attribute option for one store')
            ->addArgument(self::ATTRIBUTE_CODE, InputArgument::REQUIRED, 'Attribute Code')
            ->addArgument(self::OPTION_ID, InputArgument::REQUIRED, 'Option ID')
            ->addArgument(self::STORE_ID, InputArgument::REQUIRED, 'Store ID')
            ->addArgument(self::LABEL, InputArgument::REQUIRED, 'New label');
    }

    protected function getStoreId(InputInterface $input): int
    {
        return (int) $input->getArgument(self::STORE_ID);
    }
}
