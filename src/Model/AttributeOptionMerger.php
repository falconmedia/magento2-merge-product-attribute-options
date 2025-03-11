<?php

namespace FalconMedia\MergeAttributeOptions\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;

class AttributeOptionMerger
{
    private $resource;
    private $attributeRepository;

    public function __construct(
        ResourceConnection $resource,
        AttributeRepositoryInterface $attributeRepository
    ) {
        $this->resource = $resource;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Merge attribute options
     *
     * @param string $attributeCode
     * @param array $sourceOptionIds
     * @param int $targetOptionId
     * @return array
     * @throws LocalizedException
     */
    public function mergeOptions(string $attributeCode, array $sourceOptionIds, int $targetOptionId): array
    {
        $connection = $this->resource->getConnection();
        $attribute = $this->attributeRepository->get('catalog_product', $attributeCode);

        if (!$attribute->getId()) {
            throw new LocalizedException(__('Attribute not found.'));
        }

        $attributeId = $attribute->getAttributeId();
        $table = $this->resource->getTableName('catalog_product_entity_int');
        $optionTable = $this->resource->getTableName('eav_attribute_option');
        $valueTable = $this->resource->getTableName('eav_attribute_option_value');

        $mergedCounts = [];

        foreach ($sourceOptionIds as $sourceOptionId) {
            // Count affected products before merging
            $select = $connection->select()
                ->from($table, ['count' => new \Zend_Db_Expr('COUNT(*)')])
                ->where('attribute_id = ?', $attributeId)
                ->where('value = ?', $sourceOptionId);

            $count = (int) $connection->fetchOne($select);
            $mergedCounts[$sourceOptionId] = $count;

            // Update product attributes to target option ID
            $connection->update(
                $table,
                ['value' => $targetOptionId],
                ['attribute_id = ?' => $attributeId, 'value = ?' => $sourceOptionId]
            );
        }

        // Delete unused options
        $deletedOptions = [];
        foreach ($sourceOptionIds as $sourceOptionId) {
            // Check if any products still use this option
            $select = $connection->select()
                ->from($table, ['count' => new \Zend_Db_Expr('COUNT(*)')])
                ->where('attribute_id = ?', $attributeId)
                ->where('value = ?', $sourceOptionId);

            $count = (int) $connection->fetchOne($select);

            if ($count === 0) {
                // Delete from eav_attribute_option and eav_attribute_option_value
                $connection->delete($valueTable, ['option_id = ?' => $sourceOptionId]);
                $connection->delete($optionTable, ['option_id = ?' => $sourceOptionId]);

                $deletedOptions[] = $sourceOptionId;
            }
        }

        return ['mergedCounts' => $mergedCounts, 'deletedOptions' => $deletedOptions];
    }
}
