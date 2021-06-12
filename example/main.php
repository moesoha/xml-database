<?php

require_once(__DIR__ . "/../vendor/autoload.php");

use SohaJin\Toys\XmlDatabase\Expression\Expr;
use SohaJin\Toys\XmlDatabase\Store\FileStore;
use SohaJin\Toys\XmlDatabase\Store\RedisStore;
use SohaJin\Toys\XmlDatabase\XmlDatabase;
use SohaJin\Course202001\XmlDatabaseProgram\Entity\Task;

$store = in_array('-redis', $argv)
	? new RedisStore(keyPrefix: 't:')
	: new FileStore(__DIR__.'/var/', 't_');
echo 'XmlStore is '.$store::class.PHP_EOL;

$db = (new XmlDatabase('todo', $store))
	->addEntityClass(Task::class);

echo "--find by primary key--".PHP_EOL;
var_dump(
	$db->getEntityManager()
		->createQueryBuilder(Task::class)
		->findByPrimaryKey(114514)
		->getSingleResult()
);

$test = (new Task(114514))->setName('current time')->setTime(time());
$db->getEntityManager()->update((new Task(1))->setName('test 1!'));
$db->getEntityManager()->update((new Task(2))->setName('test 2!'));
$db->getEntityManager()->update((new Task(3))->setName('test 3!'));
$db->getEntityManager()->update((new Task(4))->setName('test 4!'));
$db->getEntityManager()->update((new Task(5))->setName('test 5!'));
$db->getEntityManager()->update((new Task(6))->setName('text 6!'));
$db->getEntityManager()->update($test);
$db->getEntityManager()->persist();

echo "--find by primary key--".PHP_EOL;
var_dump(
	$db->getEntityManager()
		->createQueryBuilder(Task::class)
		->findByPrimaryKey(114514)
		->getSingleResult()
);

$qb = $db->getEntityManager()->createQueryBuilder(Task::class);
$qb->orWhere(Expr::le($qb->fieldOp('id'), 2))
	->orWhere(Expr::gt($qb->fieldOp('id'), 4))
	->andWhere(Expr::contains($qb->fieldOp('name'), 'test'))
;
echo "--find by conditions--".PHP_EOL;
var_dump($qb->getXPath());
echo "[ID]\t[Name]".PHP_EOL;
foreach($qb->getResult() as $item) {
	/** @var $item Task */
	echo "{$item->getId()}\t{$item->getName()}".PHP_EOL;
}
