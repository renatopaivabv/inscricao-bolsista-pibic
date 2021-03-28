<?php
//Modo produção
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

require_once 'vendor/autoload.php';
define('DS', DIRECTORY_SEPARATOR);

if(isset($_GET['teste'])){
	//Modo desenvolvimento
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	$arquivo = 'lattes-renato-farias-de-paiva.pdf';
	$caminho = __DIR__ . DS . 'historicos' . DS .  $arquivo;
	echo '<pre>';
	print_r(lerLattes($caminho));
	exit();
}

if(isset($_FILES['arquivo'])){
	if(isset($_POST['edital']) && isset($_POST['processo'])){
		
		$dados = lerLattes($_FILES['arquivo']['tmp_name']);
		
		if(isset($dados['erro']) && $dados['erro'] == false){
			$diretorio = __DIR__ . DS .'arquivos'. DS . $_POST['edital'] . DS . $_POST['processo'] . DS;
		
			if(!file_exists($diretorio))
				mkdir($diretorio, 777, true);
			$caminho_arquivo = $diretorio . 'lattes-' . slug($dados['nome'].'-'. $dados['atualizacao']) . '.pdf';
			move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminho_arquivo);
		}
	}else{
		$dados['erro'] = true;
		$dados['msg_erro'] = 'Problemas na requisição. Dados básicos do projeto não foram enviados!';
	} 
}else{
	$dados['erro'] = true;
	$dados['msg_erro'] = 'Problemas na requisição. Arquivo não enviado!';
}

echo json_encode($dados, JSON_UNESCAPED_UNICODE);

function lerLattes($caminho)
{
	if(!file_exists($caminho))
		die(json_encode("Arquivo {$caminho} não encontrado", JSON_UNESCAPED_UNICODE));
	$parser = new \Smalot\PdfParser\Parser();
	$pdf    = $parser->parseFile($caminho);
	$detalhes = $pdf->getDetails();

	if(!isset($detalhes['Creator'])){
		return "Erro: Nao foi possivel ler o arquivo. Tenha certeza de ter gerado através do Sigaa";
	}else{
		$texto = $pdf->getText();
		return retornaDados($texto);
	}
}

function retornaDados($texto)
{
	$dados['erro'] = false;
	$dados['msg_erro'] = [];
	
	if(preg_match_all('/http:\/\/lattes.cnpq.br\/\d{12,16}/', $texto, $curriculo )){
		$dados['url'] = isset($curriculo[0][0]) ? trim($curriculo[0][0]) : "";
	}else{
		$dados['erro'] = true;
		$dados['msg_erro'][] = 'Não foi possível ler [URL do currículo]';
	}
	
	if(preg_match_all('/em (\d{2}\/\d{2}\/\d{4})/', $texto, $atualizacao )){
		$dados['atualizacao'] = isset($atualizacao[1][0]) ? trim($atualizacao[1][0]) : "";
	}else{
		$dados['erro'] = true;
		$dados['msg_erro'][] = 'Não foi possível ler [Atualização]';
	}

	if(preg_match_all('/[0-9]{3}\.[0-9]{3}\.[0-9]{3}\-[0-9]{2}/', $texto, $cpf )){
		$dados['cpf'] = isset($cpf[0][0]) ? trim($cpf[0][0]) : "";
	}else{
		$dados['erro'] = true;
		$dados['msg_erro'][] = 'Não foi possível ler [CPF]';
	}
	
	if(preg_match_all('/Nome(.*?)\nDados/', $texto, $nome )){
		$dados['nome'] = isset($nome[1][0]) ? trim($nome[1][0]) : "";
	}else{
		$dados['erro'] = true;
		$dados['msg_erro'][] = 'Não foi possível ler [Nome]';
	}
	
	if(preg_match_all('/Sexo(.*?)\nCor/', $texto, $sexo )){
		$dados['sexo'] = isset($sexo[1][0]) ? trim($sexo[1][0]) : "";
	}else{
		$dados['erro'] = true;
		$dados['msg_erro'][] = 'Não foi possível ler [Sexo]';
	}

	if(preg_match_all('/Nascimento(.*?)\-/', $texto, $nascimento )){
		$dados['nascimento'] = isset($nascimento[1][0]) ? trim($nascimento[1][0]) : "";
	}else{
		$dados['erro'] = true;
		$dados['msg_erro'][] = 'Não foi possível ler [Nascimento]';
	}

	if(preg_match_all('/Telefone: (.*?)\n/', $texto, $telefone )){
		$dados['telefone'] = isset($telefone[1][0]) ? trim($telefone[1][0]) : "";
	}else{
		$dados['erro'] = true;
		$dados['msg_erro'][] = 'Não foi possível ler [Telefone]';
	}

	if(preg_match_all('/Celular(.*?)\n/', $texto, $celular )){
		$dados['celular'] = isset($celular[1][0]) ? trim($celular[1][0]) : "";
	}else{
		$dados['erro'] = true;
		$dados['msg_erro'][] = 'Não foi possível ler [Celular]';
	}
	
	if(preg_match_all('/E-mail para contato : (.*?)\n/', $texto, $email_contato )){
		$dados['email_contato'] = isset($email_contato[1][0]) ? trim($email_contato[1][0]) : "";
	}else{
		$dados['erro'] = true;
		$dados['msg_erro'][] = 'Não foi possível ler [Email contato]';
	}

	if(preg_match_all('/E-mail alternativo(.*?)\n/', $texto, $email_alternativo )){
		$dados['email_alternativo'] = isset($email_alternativo[1][0]) ? trim($email_alternativo[1][0]) : "";
	}else{
		$dados['msg_aviso'][] = 'Não foi possível ler [Email alternativo]';
	}

	return $dados;
}

function slug($str)
{
	$str = strtolower(trim($str));
	$str = preg_replace('/[^a-z0-9-]/', '-', $str);
	$str = preg_replace('/-+/', "-", $str);
	return $str;
}
