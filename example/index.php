<?php
ini_set('short_open_tag', 'on');
error_reporting(E_ALL);
require_once(__DIR__."/../vendor/autoload.php");

use SohaJin\Course202001\XmlDatabaseProgram\Entity\Group;
use SohaJin\Course202001\XmlDatabaseProgram\Entity\Task;
use SohaJin\Toys\XmlDatabase\Expression\Expr;
use SohaJin\Toys\XmlDatabase\Store\FileStore;
use SohaJin\Toys\XmlDatabase\XmlDatabase;

$db = (new XmlDatabase('todo', new FileStore(__DIR__.'/var/')))
	->addEntityClass(Task::class)
	->addEntityClass(Group::class)
;
$em = $db->getEntityManager();

if (!$em->createQueryBuilder(Group::class)->getCount()) {
	$em->update((new Group())->setName('默认分组')->setDefaultTime(time()));
	$em->persist();
}
/** @var ?Group $selectedGroup */
$selectedGroup = null;
if (isset($_GET['group'])) {
	$selectedGroup = $em->createQueryBuilder(Group::class)->findByPrimaryKey(intval($_GET['group']))->getSingleResult();
}
/** @var Group[] $groups */
$groups = $em->createQueryBuilder(Group::class)->getResult();
if (!$selectedGroup) {
	$selectedGroup = array_reduce($groups, fn($max, $item) => ($max && ($item->getDefaultTime() < $max->getDefaultTime())) ? $max : $item, null);
}

switch ($_POST['_action']) {
	case 'todo.add':
		if (empty($content = trim($_POST['name'] ?? ''))) break;
		$todo = new Task();
		$todo->setName($content);
		$todo->setGroupKey($selectedGroup->getKey());
		$em->update($todo);
		$em->persist();
		break;
	case 'todo.mark':
		if (empty($id = trim($_POST['id'] ?? ''))) break;
		if (empty($solved = trim($_POST['solved'] ?? ''))) break;
		/** @var Task $todo */
		$todo = $em->createQueryBuilder(Task::class)->findByPrimaryKey(intval($id))->getSingleResult();
		if (!$todo) break;
		$todo->setSolved(filter_var($solved, FILTER_VALIDATE_BOOLEAN));
		$em->update($todo);
		$em->persist();
		break;
	case 'todo.delete':
		if (empty($id = trim($_POST['id'] ?? ''))) break;
		/** @var Task $todo */
		$todo = $em->createQueryBuilder(Task::class)->findByPrimaryKey(intval($id))->getSingleResult();
		if (!$todo) break;
		$em->delete($todo);
		$em->persist();
		break;
	case 'group.add':
		if (empty($name = trim($_POST['name'] ?? ''))) break;
		$group = (new Group())->setName($name);
		$em->update($group);
		$em->persist();
		header("Location: ?group={$group->getKey()}");
		break;
	case 'group.edit':
		if (empty($id = trim($_POST['id'] ?? ''))) break;
		if (empty($name = trim($_POST['name'] ?? ''))) break;
			/** @var Group $group */
		$group = $em->createQueryBuilder(Group::class)->findByPrimaryKey(intval($id))->getSingleResult();
		if (!$group) break;
		$group->setName($name);
		$em->update($group);
		$em->persist();
		header("Location: ?group=$name");
		break;
	case 'group.default':
		if (empty($id = trim($_POST['id'] ?? ''))) break;
		/** @var Group $group */
		$group = $em->createQueryBuilder(Group::class)->findByPrimaryKey(intval($id))->getSingleResult();
		if (!$group) break;
		$group->setDefaultTime(time());
		$em->update($group);
		$em->persist();
		break;
	case 'group.delete':
		if (empty($id = trim($_POST['id'] ?? ''))) break;
		/** @var Group $group */
		$group = $em->createQueryBuilder(Group::class)->findByPrimaryKey(intval($id))->getSingleResult();
		if (!$group) break;
		$em->delete($group);
		$em->persist();
		header("Location: /");
		break;
}
$qb = $em->createQueryBuilder(Task::class);
/** @var Task[] $tasks */
$tasks = $qb->andWhere(Expr::eq($qb->fieldOp('groupKey'), $selectedGroup->getKey()))->getResult();
usort($groups, fn($a, $b) => ($a->getKey() < $b->getKey()) ? -1 : 1);
?>
<html>
	<head>
		<title>待办事项</title>
	</head>
	<body>
		<h1>待办事项</h1>
		<p>
			<?php foreach ($groups as $item): ?>
				<?php if($item->getName() === $selectedGroup->getName()): ?><span style="font-style: italic;"><?=$item->getName()?></span>
				<?php else: ?><a href="?group=<?=htmlentities($item->getKey())?>"><?=$item->getName()?></a>
				<?php endif; ?>
				|
			<?php endforeach; ?>
			<button onclick="document.getElementById('group-add').style.display = 'inherit';">添加分组</button>
			<button onclick="document.getElementById('group-edit').style.display = 'inherit';">编辑当前分组</button>
		</p>
		<div id="group-add" style="display: none;">
			<form method="post">
				<input type="hidden" name="_action" value="group.add" />
				分组名: <input type="text" name="name" />
				<br />
				<input type="submit" value="提交" />
				<input onclick="document.getElementById('group-add').style.display = 'none';" type="reset" value="取消" />
			</form>
		</div>
		<div id="group-edit" style="display: none;">
			<form method="post">
				<input type="hidden" class="input-action" name="_action" value="" />
				<input type="hidden" name="id" value="<?=$selectedGroup->getKey()?>" />
				分组名: <input type="text" name="name" value="<?=htmlentities($selectedGroup->getName())?>" />
				<input type="submit" onclick="return taskSubmit(this);" data-action="group.edit" value="修改" />
				<br />
				<input type="submit" onclick="return taskSubmit(this);" data-action="group.default" value="设为默认" />
				<input type="submit" onclick="return taskSubmit(this);" data-action="group.delete" value="删除" />
				<input onclick="document.getElementById('group-edit').style.display = 'none';" type="reset" value="取消" />
			</form>
		</div>
		<table>
			<tr>
				<td>编号</td>
				<td>已完成</td>
				<td>事项</td>
				<td>添加时间</td>
				<td>操作</td>
			</tr>
			<?php foreach ($tasks as $item): ?>
				<form method="post">
					<tr>
						<td><?=$item->getId()?><input type="hidden" name="id" value="<?=$item->getId()?>" /></td>
						<td><?=$item->isSolved() ? '✓' : ''?><input type="hidden" name="solved" value="<?=$item->isSolved() ? 'false' : 'true'?>" /></td>
						<td><?=htmlspecialchars($item->getName())?></td>
						<td><?=date('Y-m-d H:i:s', $item->getCreateTime())?></td>
						<td>
							<input type="hidden" class="input-action" name="_action" value="" />
							<input type="submit" onclick="return taskSubmit(this);" data-action="todo.mark" value="标记<?=$item->isSolved() ? '未完成' : '已完成'?>" />
							<input type="submit" onclick="return taskSubmit(this);" data-action="todo.delete" value="删除" />
						</td>
					</tr>
				</form>
			<?php endforeach; ?>
			<form method="post">
				<input type="hidden" name="_action" value="todo.add" />
				<tr>
					<td>+</td>
					<td></td>
					<td><input type="text" name="name" placeholder="待办事项" /></td>
					<td><input type="submit" value="新增" /></td>
					<td></td>
				</tr>
			</form>
		</table>
		<script>
			function taskSubmit(el) {
				el.parentElement.getElementsByClassName('input-action').item(0).value = el.getAttribute('data-action');
				return true;
			}
		</script>
	</body>
</html>
