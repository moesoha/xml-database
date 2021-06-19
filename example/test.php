<?php

require_once(__DIR__."/../vendor/autoload.php");

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

echo PHP_EOL."--XSD--".PHP_EOL;
echo ($db->getEntityDocument(Task::class)->generateXmlSchema()).PHP_EOL;

echo PHP_EOL."--find by primary key--".PHP_EOL;
var_dump(
	$db->getEntityManager()
		->createQueryBuilder(Task::class)
		->findByPrimaryKey(114514)
		->getSingleResult()
);

$test = (new Task(114514))->setName('current time')->setTime(time());
$db->getEntityManager()->update((new Task())->setName('test 1!'));
$db->getEntityManager()->update((new Task())->setName('test 2!'));
$db->getEntityManager()->update((new Task())->setName('test 3!'));
$db->getEntityManager()->update((new Task())->setName('test 4!'));
$db->getEntityManager()->update((new Task())->setName('test 5!'));
$db->getEntityManager()->update($test6 = (new Task())->setName('text 6!'));
$db->getEntityManager()->update($test);
$db->getEntityManager()->persist();

$db->getEntityManager()->delete($test6);
$db->getEntityManager()->update((new Task())->setName('test 7!'));
$db->getEntityManager()->update((new Task())->setName('test 8!'));
$db->getEntityManager()->update((new Task())->setName('test 9!'));
$db->getEntityManager()->update((new Task())->setName('test 10!'));
$db->getEntityManager()->persist();

echo PHP_EOL."--find by primary key--".PHP_EOL;
var_dump(
	$db->getEntityManager()
		->createQueryBuilder(Task::class)
		->findByPrimaryKey(114514)
		->getSingleResult()
);

echo PHP_EOL."--find by conditions--".PHP_EOL;
$qb = $db->getEntityManager()->createQueryBuilder(Task::class);
$qb->orWhere(Expr::le($qb->fieldOp('id'), 2))
	->orWhere(Expr::gt($qb->fieldOp('id'), 4))
	->andWhere(Expr::contains($qb->fieldOp('name'), 'test'))
;
echo "Generated XPath: ".$qb->getXPath().PHP_EOL;
echo "[ID]\t[Name]".PHP_EOL;
foreach($qb->getResult() as $item) {
	/** @var $item Task */
	echo "{$item->getId()}\t{$item->getName()}".PHP_EOL;
}
