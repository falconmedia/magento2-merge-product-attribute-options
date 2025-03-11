<?php

namespace FalconMedia\MergeAttributeOptions\Console;

use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\App\Area;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use FalconMedia\MergeAttributeOptions\Model\AttributeOptionMerger;

class MergeAttributeOptions extends Command
{
    const ATTRIBUTE_CODE = 'attribute_code';
    const SOURCE_OPTION_IDS = 'source_option_ids';
    const TARGET_OPTION_ID = 'target_option_id';

    private $state;
    private $attributeOptionMerger;

    public function __construct(
        State $state,
        AttributeOptionMerger $attributeOptionMerger
    ) {
        $this->state = $state;
        $this->attributeOptionMerger = $attributeOptionMerger;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('falconmedia:attribute:merge-options')
            ->setDescription('Merge attribute options into one option')
            ->addArgument(self::ATTRIBUTE_CODE, InputArgument::REQUIRED, 'Attribute Code')
            ->addArgument(self::SOURCE_OPTION_IDS, InputArgument::REQUIRED, 'Comma-separated source option IDs')
            ->addArgument(self::TARGET_OPTION_ID, InputArgument::REQUIRED, 'Target option ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);

            $attributeCode = $input->getArgument(self::ATTRIBUTE_CODE);
            $sourceOptionIds = explode(',', $input->getArgument(self::SOURCE_OPTION_IDS));
            $targetOptionId = (int) $input->getArgument(self::TARGET_OPTION_ID);

            $output->writeln("<info>Merging attribute options for '{$attributeCode}'</info>");

            // Use the model to merge options
            $result = $this->attributeOptionMerger->mergeOptions($attributeCode, $sourceOptionIds, $targetOptionId);

            // Display merge results in a table
            $table = new Table($output);
            $table->setHeaders(['Source Option ID', 'Target Option ID', 'Merged Product Count', 'Status']);

            foreach ($result['mergedCounts'] as $optionId => $count) {
                $status = in_array($optionId, $result['deletedOptions']) ? 'Deleted' : 'Merged';
                $table->addRow([$optionId, $targetOptionId, $count, $status]);
            }

            $table->render();

            return Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }
    }
}
