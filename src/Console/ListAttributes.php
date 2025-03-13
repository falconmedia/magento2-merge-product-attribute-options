<?php

namespace FalconMedia\MergeAttributeOptions\Console;

use Magento\Eav\Model\Config;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class ListAttributes extends Command
{
    protected Config $eavConfig;
    protected State $appState;

    public function __construct(Config $eavConfig, State $appState)
    {
        $this->eavConfig = $eavConfig;
        $this->appState = $appState;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('falconmedia:attribute:list')
            ->setDescription('List all product attributes with their ID, code, admin name, and type.')
            ->addOption(
                'type',
                'T',
                InputOption::VALUE_OPTIONAL,
                'Filter attributes by type (e.g., dropdown, multiselect, text, etc.)'
            )
            ->addOption(
                'code',
                'C',
                InputOption::VALUE_OPTIONAL,
                'Filter attributes by code (partial match, case-insensitive)'
            )
            ->addOption(
                'name',
                'N',
                InputOption::VALUE_OPTIONAL,
                'Filter attributes by name (partial match, case-insensitive)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->appState->setAreaCode('adminhtml');

            $attributes = $this->eavConfig->getEntityAttributes('catalog_product');

            if (empty($attributes)) {
                $output->writeln('<comment>No attributes found.</comment>');
                return Cli::RETURN_SUCCESS;
            }

            // Get filter values
            $filterType = $input->getOption('type');
            $filterCode = $input->getOption('code');
            $filterName = $input->getOption('name');

            // Prepare table
            $table = new Table($output);
            $table->setHeaders(['Attribute ID', 'Attribute Code', 'Admin Name', 'Type']);
            $rowCount = 0; // Track row count

            foreach ($attributes as $attribute) {
                $attributeId = $attribute->getAttributeId();
                $attributeCode = $attribute->getAttributeCode();
                $attributeName = $attribute->getFrontendLabel() ?: '-';
                $attributeType = $attribute->getFrontendInput() ?: 'Unknown';

                // Apply filters
                if ($filterType && strtolower($attributeType) !== strtolower($filterType)) {
                    continue;
                }
                if ($filterCode && stripos($attributeCode, $filterCode) === false) {
                    continue;
                }
                if ($filterName && stripos($attributeName, $filterName) === false) {
                    continue;
                }

                $table->addRow([$attributeId, $attributeCode, $attributeName, $attributeType]);
                $rowCount++;
            }

            if ($rowCount > 0) {
                $table->render();
            } else {
                $output->writeln('<comment>No attributes found matching the filters.</comment>');
            }

        } catch (LocalizedException $e) {
            $output->writeln("<error>Error: {$e->getMessage()}</error>");
            return Cli::RETURN_FAILURE;
        }
        return Cli::RETURN_SUCCESS;
    }
}
