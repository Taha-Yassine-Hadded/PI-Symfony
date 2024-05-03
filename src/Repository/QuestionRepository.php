<?php

namespace App\Repository;

use App\Entity\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Question>
 *
 * @method Question|null find($id, $lockMode = null, $lockVersion = null)
 * @method Question|null findOneBy(array $criteria, array $orderBy = null)
 * @method Question[]    findAll()
 * @method Question[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    public function findQuestionsByEvaluation($evaluationId)
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.evaluation = :evaluationId')
            ->setParameter('evaluationId', $evaluationId)
            ->getQuery()
            ->getResult();
    }

    public function findCorrectOptionForQuestion($questionId)
    {
    return $this->createQueryBuilder('q')
        ->join('q.options', 'o')
        ->andWhere('q.id = :questionId')
        ->andWhere('o.isCorrect = :isCorrect')
        ->setParameter('questionId', $questionId)
        ->setParameter('isCorrect', true)
        ->getQuery()
        ->getOneOrNullResult();
    }
//    /**
//     * @return Question[] Returns an array of Question objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('q')
//            ->andWhere('q.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('q.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Question
//    {
//        return $this->createQueryBuilder('q')
//            ->andWhere('q.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
