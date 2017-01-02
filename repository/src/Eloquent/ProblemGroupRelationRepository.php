<?php
/**
 * Created by PhpStorm.
 * User: lumin
 * Date: 16-12-24
 * Time: 下午2:15
 */

namespace NEUQOJ\Repository\Eloquent;


use NEUQOJ\Repository\Contracts\SoftDeletionInterface;
use NEUQOJ\Repository\Traits\SoftDeletionTrait;
use Illuminate\Support\Facades\DB;

class ProblemGroupRelationRepository extends AbstractRepository implements SoftDeletionInterface
{
    public function model()
    {
        return "NEUQOJ\Repository\Models\ProblemGroupRelation";
    }

    use SoftDeletionTrait;

    public function getRelationsByNums(int $groupId,array $problemNums,array $columns = ['*'])
    {
        return $this->model
            ->where('problem_group_id',$groupId)
            ->whereIn('problem_num',$problemNums)->get($columns);
    }

    public function getRelationsByIds(int $groupId,array $problemIds,array $columns =  ['*'])
    {
        return $this->model
            ->where('problem_group_id',$groupId)
            ->whereIn('problem_id',$problemIds)->get($columns);
    }

    public function getProblemInfoInGroup(int $groupId)
    {

        $sql ="select pid,problem_score,problem_title,pnum,accepted,submit from (select problem_id pid,problem_score,problem_num pnum,problem_title from problem_group_relations where problem_group_id = $groupId
                ) problem
                left join (select problem_id pid1,count(1) accepted from solutions where result=4 and problem_group_id=$groupId group by pid1) p1 on problem.pid=p1.pid1
                left join (select problem_id pid2,count(1) submit from solutions where problem_group_id=$groupId group by pid2) p2 on problem.pid=p2.pid2
		order by pnum";

        return DB::select(DB::raw($sql));
    }

    //根据题目组中的题号获取题目，无视题目可见性
    public function getProblemByNum(int $groupId,int $problemNum)
    {
        return $this->model
            ->where('problem_group_id',$groupId)
            ->where('problem_num',$problemNum)
            ->join('problems','problems.id','=','problem_group_relations.problem_id')
            ->select('problems.*')
            ->get();
    }

    public function deleteWhereIn(string $param,array $data)
    {
        return $this->model->whereIn($param,$data)->delete();
    }
}