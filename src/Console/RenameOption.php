<?php

namespace FalconMedia\MergeAttributeOptions\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Renames the admin label (store 0) of an attribute option.
 */
class RenameOption extends AbstractRenameOption
{
    protected function configure(): void
    {
        $this->setName('falconmedia:attribute:rename-option')
            ->setDescription('Rename the admin label (store 0) of an attribute option')
            ->addArgument(self::ATTRIBUTE_CODE, InputArgument::REQUIRED, 'Attribute Code')
            ->addArgument(self::OPTION_ID, InputArgument::REQUIRED, 'Option ID')
            ->addArgument(self::LABEL, InputArgument::REQUIRED, 'New label');
    }

    protected function getStoreId(InputInterface $input): int
    {
        return 0;
    }
}
