<?php

	session_start();
	$baseDatabase = 'db.db3';
	$indDatabase = 'db_' . $_SERVER['REMOTE_ADDR'] . '.db3';
	if ( !file_exists($indDatabase) ) {
		copy($baseDatabase, $indDatabase);
	}
	$conn = new pdo('sqlite:' . $indDatabase);
	if ( isset($_REQUEST['action']) ) {
		$action = $_REQUEST['action'];
	} else {
		$action = "inicio";
	}
?>
<html>
	<head>
	</head>
	<body>
		<?php if ( isset($_SESSION['usuario']) ) { ?>
			<a style="float:right; border: 1px solid black; color: black; text-decoration: none;" href="index.php?action=logout"><?php print $_SESSION['usuario']; ?></a>
		<?php
			$sql = "select * from access where userid = '".$_SESSION['usuario']."' and functionality = '".$action."'";
			$rs = $conn->query($sql)->fetchAll();
			if ( count($rs) == 0 and $action != 'inicio' ) {
				$action = 'erro';
			}
		} else { 
			$action = 'login';
		} ?>
		<ul type="circle">
			<li><a href="index.php?action=inicio"><b>T</b>AREFAS</a></li>
			<li><a href="index.php?action=create"><b>C</b>REATE</a></li>
			<li><a href="index.php?action=read">  <b>R</b>EAD  </a></li>
			<li><a href="index.php?action=update"><b>U</b>PDATE</a></li>
			<li><a href="index.php?action=delete"><b>D</b>ELETE</a></li>
		</ul>
<?php
	@$sql = "insert into trails values ('". $_SESSION['usuario'] ."', '" . $action . "', date('now'))";
	$conn->exec($sql);
	switch ( $action ) {
		case "create":
			if ( isset($_REQUEST['title']) && isset($_REQUEST['price']) ) {
				$sql = "insert into game (id, title, price, modifiedat) values (null, '" . $_REQUEST['title'] . "', '" . $_REQUEST['price'] . "', date('now'))";
				$conn->exec($sql);
				header("Location:index.php?action=read");
			} else {
?>
		<form action="index.php?action=create" method="post">
			<p>
				Título:<br/>
				<input type="text" name="title" autocomplete="off" />
			</p>
			<p>
				Preço:<br/>
				<input type="text" name="price" autocomplete="off" />
			</p>
			<p>
				<input type="submit" value="Cadastrar"/>
			</p>
		</form>
<?php
			}
			break;
		case "read":
?>
		<form action="index.php?action=read" method="post">
			<input name="pesquisa" autocomplete="off" /> 
			<input type="submit" value="Pesquisar" />
		</form>
		<table border>
			<caption><b>Lista de Video Games</b></caption>
			<tr>
				<th>ID</th>
				<th>Título</th>
				<th>Preço</th>
				<th>Alterado Em</th>
				<th>Editar</th>
				<th>Excluir</th>
			</tr>
<?php
			$sql = "select id, title, price, modifiedat from game where buys is null order by modifiedat ";
			if (isset($_REQUEST['pesquisa'])) {
				$sql = "select id, title, price, modifiedat from game where title like '%".$_REQUEST['pesquisa']."%' and buys is null order by modifiedat ";
			}
			$rs = $conn->query($sql);
			while ( $row = $rs->fetch(PDO::FETCH_BOTH) ) {
?>
			<tr>
				<td><?=$row['id']?></td>
				<td><?=$row['title']?></td>
				<td><?=$row['price']?></td>
				<td><?=$row['modifiedat']?></td>
				<td><a href="index.php?action=update&id=<?=$row['id']?>"> X </a></td>
				<td><a href="index.php?action=delete&id=<?=$row['id']?>"> X </a></td>
			</tr>
<?php
			}
?>					
		</table>
<?php
			break;
		case "update":
			if ( isset($_REQUEST['id']) && isset($_REQUEST['descricao']) && isset($_REQUEST['preco']) ) {
				$sql = "update game set title = '" . $_REQUEST['descricao'] . "', price = '" . $_REQUEST['preco'] . "', modifiedat = date('now') where id = '" . $_REQUEST['id'] . "'";
				$conn->exec($sql);
				header("Location:index.php?action=read");
			} elseif ( isset($_REQUEST['id']) ) {
				$sql = "select id, title, price from game where id = '" . $_REQUEST['id'] . "'";
				$rs = $conn->query($sql);
				$row = $rs->fetch(PDO::FETCH_BOTH);
?>
		<form action="index.php?action=update" method="post">
			<input type="hidden" name="id" value="<?=$row['id']?>" autocomplete="off" />
			<p>
				Descrição:<br/>
				<input type="text" name="descricao" value="<?=$row['title']?>" autocomplete="off" />
			</p>
			<p>
				Preço:<br/>
				<input type="text" name="preco" value="<?=$row['price']?>" autocomplete="off" />
			</p>
			<p>
				<input type="submit" value="Atualizar"/>
			</p>
		</form>
<?php
			} else {
				header("Location:index.php?action=read");
			}
			break;
		case "delete":
			@$sql = "delete from game where id = '" . $_REQUEST['id'] . "'";
			$conn->exec($sql);
			header("Location:index.php?action=read");
			break;
		case "login":
			if (isset($_REQUEST['usuario']) && isset($_REQUEST['senha'])) {
				$sql = "select * from user where id = '".$_REQUEST['usuario']."' and password = '".md5($_REQUEST['senha'])."' and loginattempts < 3";
				$rs = $conn->query($sql)->fetchAll();
				if ( count($rs) > 0 ) {
					foreach ( $rs as $registro ) {
						$_SESSION['usuario'] = $registro['id'];
					}
					$sql = "update user set loginattempts = 0 where id = '".$_SESSION['usuario']."'";
					$conn->exec($sql);
				} else {
					$sql = "update user set loginattempts = (loginattempts + 1) where id = '" . $_REQUEST['usuario'] . "'";
					$conn->exec($sql);
				}
				header ('location: index.php');
			} else {
?>
				<form method="post" action="index.php?action=login">
					<label>Identificação</label><br/>
					<input type="text" name="usuario" autocomplete="off" /><br/>
					<label>Senha</label><br/>
					<input type="password" name="senha" autocomplete="off" /><br/>
					<input type="submit" />
				</form>
				<p>Esta é a tarefa de hoje. Seguem orientações e questões.</p>
				<ol>
					<li>Autentique-se no sistema.</li>
					<li>Visualize o dicionário de dados da aplicação.</li>
					<li>Quais são os atributos da tabela responsável pelo armazenamento dos usuários do sistema? **</li>
					<li>Crie uma tabela conforme a estrutura: resposta (questao integer, texto text).</li>
					<li>Quais usuários têm permissão de acesso à funcionalidade inicio? **</li>
					<li>Quantas vezes o usuário jim tentou realizar autenticação no sistema sem sucesso? **</li>
					<li>Quantos video games existem registrados no sistema? **</li>
					<li>Quantas vezes o usuário mary acessou a funcionalidade create do sistema, de acordo com os registros de auditoria? **</li>
					<li>Crie um usuário com o seu nome e conceda permissões de acesso à todas funcionalidades do sistema.</li>
					<li>Remova todas permissões de acesso do usuário john.</li>
					<li>**Insira as respostas referentes às questões marcadas com ** na tabela resposta.<br/>**Não serão aceitas respostas escritas em papel ou enviadas por e-mail.<br/>**A avaliação será realizada por análise do banco de dados.</li>
				</ol>
<?php
			}
			break;
		case "logout":
			session_destroy();
			header("Location:index.php");
			break;
		case "erro":
			print "<h1>Você não tem permissão de acesso.</h1>";
			break;
	}
?>
	</body>
</html>