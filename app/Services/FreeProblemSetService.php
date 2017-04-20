<?php
/**
 * Created by PhpStorm.
 * User: lumin
 * Date: 17/4/19
 * Time: 下午10:13
 */

namespace NEUQOJ\Services;


use Illuminate\Support\Facades\File;
use League\Flysystem\Directory;
use NEUQOJ\Common\Utils;
use NEUQOJ\Repository\Eloquent\ProblemRepository;
use NEUQOJ\Services\Contracts\FreeProblemSetServiceInterface;

class FreeProblemSetService implements FreeProblemSetServiceInterface
{

    private $problemRepo;

    public function __construct(ProblemRepository $problemRepo)
    {
        $this->problemRepo = $problemRepo;
    }

    private function getValue($Node, $TagName)
    {
        return $Node->$TagName;
    }

    private function getAttribute($Node, $TagName, $attribute)
    {
        return $Node->children()->$TagName->attributes()->$attribute;
    }

    private function makeData(int $problemId,string $filename,$data)
    {
        $path = Utils::getProblemDataPath($problemId);

        File::put($path.$filename,$data);
    }

    public function importProblems($file, array $config)
    {
        $problemIds = [];
        $xmlDoc = simplexml_load_file($file, 'SimpleXMLElement', LIBXML_PARSEHUGE);
        $searchNodes = $xmlDoc->xpath("/fps/item");

        foreach ($searchNodes as $searchNode) {
            //echo $searchNode->title,"\n";

            $title = $searchNode->title;

            $timeLimit = $searchNode->time_limit;
            $unit = $this->getAttribute($searchNode, 'time_limit', 'unit');
            //echo $unit;
            if ($unit == 'ms') $timeLimit /= 1000;

            $memoryLimit = $this->getValue($searchNode, 'memory_limit');
            $unit = $this->getAttribute($searchNode, 'memory_limit', 'unit');
            if ($unit == 'kb') $memoryLimit /= 1024;

            $description = $this->getValue($searchNode, 'description');
            $input = $this->getValue($searchNode, 'input');
            $output = $this->getValue($searchNode, 'output');
            $sampleInput = $this->getValue($searchNode, 'sample_input');
            $sampleOutput = $this->getValue($searchNode, 'sample_output');

            $hint = $this->getValue($searchNode, 'hint');
            $source = $this->getValue($searchNode, 'source');

            $spjcode = $this->getValue($searchNode, 'spj');
            $spj = trim($spjcode) ? 1 : 0;

            // TODO 从solution节点取出标准题解

            $problem = [
                'title' => (string)$title,
                'description' => (string)$description,
                'creator_id' => $config['creator_id'],
                'creator_name' => $config['creator_name'],
                'input' => (string)$input,
                'output' => (string)$output,
                'sample_input' => (string)$sampleInput,
                'sample_output' => (string)$sampleOutput,
                'hint' => (string)$hint,
                'source' => (string)$source,
                'time_limit' => (int)$timeLimit,
                'memory_limit' => (int)$memoryLimit,
                'is_public' => $config['is_public'],
                'spj' => $spj
            ];

            $problemId = $this->problemRepo->insertWithId($problem);

            // 样例

            if (!File::makeDirectory(Utils::getProblemDataPath($problemId), $mode = 0755))
                return false;

            $this->makeData($problemId, 'sample.in', $sampleInput);
            $this->makeData($problemId, 'sample.out', $sampleOutput);

            $testInputs = $searchNode->children()->test_input;

            $testNum = 0;

            foreach ($testInputs as $testInput) {
                $this->makeData($problemId, 'test' . $testNum++ . ".in", $testInput);
            }

            $testOutputs = $searchNode->children()->test_output;

            $testNum = 0;

            foreach ($testOutputs as $testOutput) {
                $this->makeData($problemId, 'test' . $testNum++ . ".out", $testOutput);
            }

            $problemIds[] = $problemId;

        }

        return $problemIds;
    }

    public function exportProblems(array $problemIds)
    {
        // TODO: Implement exportProblems() method.
    }
}