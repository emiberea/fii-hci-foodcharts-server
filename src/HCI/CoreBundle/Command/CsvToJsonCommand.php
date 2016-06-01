<?php

namespace HCI\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CsvToJsonCommand extends ContainerAwareCommand
{
    /** @var InputInterface $input */
    private $input;

    /** @var OutputInterface $output */
    private $output;

    /** @var string $uploadDir */
    private $uploadDir;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('hci:core:csv-to-json')
            ->setDescription('Hello PhpStorm');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // initialize fields
        $this->input  = $input;
        $this->output = $output;
        $this->uploadDir = $this->getContainer()->getParameter('kernel.root_dir') . '/../web/uploads/';

        $this->computeFoodNameWithKeys();
        $this->computeNutrientName();
        $this->computeNutrientAmount();

        return 0;
    }

    protected function computeFoodNameWithKeys()
    {
        $foodNameJsonArr = $this->parseFoodName();
        $foodGroupJsonArr = $this->parseFoodGroup();
        $foodSourceJsonArr = $this->parseFoodSource();

        foreach ($foodNameJsonArr as $key => $value) {
            if (array_key_exists($value['FoodGroupID'], $foodGroupJsonArr) && array_key_exists($value['FoodSourceID'], $foodSourceJsonArr)) {
                $foodNameJsonArr[$key]['FoodGroup'] = $foodGroupJsonArr[$value['FoodGroupID']];
                $foodNameJsonArr[$key]['FoodSource'] = $foodSourceJsonArr[$value['FoodSourceID']];
            } else {
                $foodNameJsonArr[$key]['FoodGroup'] = [];
                $foodNameJsonArr[$key]['FoodSource'] = [];
            }

            echo "computeFoodNameWithKeys - Iteration: {$key}\n";
        }

        $finalArr = [];
        foreach ($foodNameJsonArr as $value) {
            $finalArr[] = $value;
        }

        $jsonStr = json_encode($finalArr);
        file_put_contents($this->uploadDir .'food_name.json', $jsonStr);
    }

    protected function computeNutrientName()
    {
        $nutrientNameJsonArr = $this->parseNutrientName();

        $finalArr = [];
        foreach ($nutrientNameJsonArr as $value) {
            $finalArr[] = $value;
        }

        $jsonStr = json_encode($finalArr);
        file_put_contents($this->uploadDir .'nutrient_name.json', $jsonStr);
    }

    protected function computeNutrientAmount()
    {
        $nutrientAmountJsonArr = $this->parseNutrientAmount();
        $jsonStr = json_encode($nutrientAmountJsonArr);
        file_put_contents($this->uploadDir .'nutrient_amount.json', $jsonStr);
    }

    private function parseFoodName()
    {
        // parsing the 'FOOD NAME.csv' file
        $filePath = $this->uploadDir . 'FOOD NAME.csv';

        return $this->parseCsvFile($filePath, ['FoodID', 'FoodCode', 'FoodGroupID', 'FoodSourceID', 'FoodDescription', 'CountryCode']);
    }

    private function parseFoodGroup()
    {
        // parsing the 'FOOD GROUP.csv' file
        $filePath = $this->uploadDir . 'FOOD GROUP.csv';

        return $this->parseCsvFile($filePath, ['FoodGroupID', 'FoodGroupCode', 'FoodGroupName']);
    }

    private function parseFoodSource()
    {
        // parsing the 'FOOD SOURCE.csv' file
        $filePath = $this->uploadDir . 'FOOD SOURCE.csv';

        return $this->parseCsvFile($filePath, ['FoodSourceID', 'FoodSourceCode', 'FoodSourceDescription']);
    }

    private function parseNutrientName()
    {
        // parsing the 'NUTRIENT NAME.csv' file
        $filePath = $this->uploadDir . 'NUTRIENT NAME.csv';

        return $this->parseCsvFile($filePath, ['NutrientID', 'NutrientCode', 'NutrientSymbol', 'NutrientUnit', 'NutrientName', 'NutrientDecimals']);
    }

    private function parseNutrientAmount()
    {
        // parsing the 'NUTRIENT AMOUNT.csv' file
        $filePath = $this->uploadDir . 'NUTRIENT AMOUNT.csv';

        return $this->parseCsvFile($filePath, ['FoodID', 'NutrientID', 'NutrientValue'], false);
    }

    /**
     * @param $filePath
     * @param array $neededKeys
     * @param bool $useKeyId
     * @return array
     */
    private function parseCsvFile($filePath, array $neededKeys, $useKeyId = true)
    {
        $rawArr = [];
        $keyArr = [];

        $row = 0;
        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if ($row == 0) {
                    $keyArr = $data;
                } else {
                    $rawArr[] = $data;
                }

                $row++;
            }
            fclose($handle);
        }

        $jsonReadyArr = [];
        foreach ($rawArr as $value) {
            $currentItem = [];
            foreach ($keyArr as $key => $keyName) {
                if (in_array($keyName, $neededKeys)) {
                    $currentItem[$keyName] = utf8_encode($value[$key]);
                }
            }

            if ($useKeyId) {
                $jsonReadyArr[$value[0]] = $currentItem;
            } else {
                $jsonReadyArr[] = $currentItem;
            }
        }

        return $jsonReadyArr;
    }
}
