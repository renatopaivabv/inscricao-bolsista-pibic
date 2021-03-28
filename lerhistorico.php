<?php
include 'vendor/autoload.php';
define('DS', DIRECTORY_SEPARATOR);

if(isset($_FILES['arquivo'])){
	if(isset($_POST['edital']) && isset($_POST['processo'])){
	
		$dados = lerHistorico($_FILES['arquivo']['tmp_name']);
		echo json_encode($dados, JSON_UNESCAPED_UNICODE);
		die();
		//Grava arquivo
		if(isset($dados['erro']) && $dados['erro'] == false){
			$diretorio = __DIR__ . DS .'arquivos'. DS . $_POST['edital'] . DS . $_POST['processo'] . DS;
		
			if(!file_exists($diretorio))
				mkdir($diretorio, 777, true);
			$nome = isset($dados['bolsista']['nome']) ? $dados['bolsista']['nome'] : 'nome-default'; 
			$emissao = isset($dados['historico']['emissao']) ? $dados['historico']['emissao'] : 'emissao-default'; 
			$caminho_arquivo = $diretorio . 'historico-' . slug($nome.'-'. $emissao) . '.pdf';
			move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminho_arquivo);
		}
	}else{
		$dados['erro'] = true;
		$dados['msg_erro'][] = 'Problemas na requisição. Dados básicos do projeto não foram enviados!';
	} 
}else{
	$dados['erro'] = true;
	$dados['msg_erro'][] = 'Problemas na requisição. Arquivo não enviado!';
}

echo json_encode($dados, JSON_UNESCAPED_UNICODE);

function lerHistorico($caminho)
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
		$explode = explode("Componentes", $texto);
		if(count($explode)>1)
			return retornaDados($explode[0] . $explode[1]);
		else
			return Array('erro'=>true, 'msg_erro'=>"Formato de arquivo errado");
		
	}
}

function retornaDados($texto)
{
	$dados['erro'] = false;
	$dados['msg_erro'] = [];
	
	if(preg_match_all('/(\d{8,12})[\s]*([A-zÀ-ú ]*)[\s]*Nome/', $texto, $matches )){
		$dados['curso']['matricula'] = isset($matches[1][0]) ? trim($matches[1][0]) : "";
		$dados['bolsista']['nome'] = isset($matches[2][1]) ? trim($matches[2][1]) : "";
	}else{
		$dados['erro'] = true;
		$dados['msg_erro'][] = 'Não foi possível ler dados basicos do histórico (Nome do bolsista)';
	}

	if(preg_match_all('/([0-9]{3}\.[0-9]{3}\.[0-9]{3}\-[0-9]{2})/', $texto, $cpf )){
	$dados['bolsista']['cpf'] = isset($cpf[0][0]) ? trim($cpf[0][0]) : "";
	}else{
		$dados['erro'] = true;
		$dados['msg_erro'][] = 'Não foi possível ler dados basicos do histórico (CPF)';
	}
	
	if(preg_match_all('/(\d{2}\/\d{2}\/\d{4} às \d{2}\:\d{2})\s+\nNacionalidade:\s*(\w*)/', $texto, $nac )){
	$dados['historico']['emissao'] = isset($nac[1][0]) ? trim($nac[1][0]) : "";
	$dados['bolsista']['nacionalidade'] = isset($nac[2][0]) ? trim($nac[2][0]) : "";
	}else{
		$dados['erro'] = true;
		$dados['msg_erro'][] = 'Não foi possível ler dados basicos do histórico (Nacionalidade)';
	}
	
	if(preg_match_all('/Data de Nascimento:\s+(\d{2}\/\d{2}\/\d{4})/', $texto, $nasc )){
		$dados['bolsista']['nascimento'] = isset($nasc[1][0]) ? trim($nasc[1][0]) : "";
	}else{
		$dados['erro'] = true;
		$dados['msg_erro'][] = 'Não foi possível ler dados basicos do histórico (Data de nascimento)';
	}
	
	if(preg_match_all('/(.*?)[\,?][\s?](.*?)[\s?](.*?)\n([A-ZÀ-Ú ]*)[\s?]([A-Z]{2})[\s?]\nEndereço:/', $texto, $endereco )){
		$dados['bolsista']['endereco'] = isset($endereco[1][0]) ? trim($endereco[1][0]) : "";
		$dados['bolsista']['numero'] = isset($endereco[2][0]) ? trim($endereco[2][0]) : "";
		$dados['bolsista']['bairro'] = isset($endereco[3][0]) ? trataBairro($endereco[3][0]) : "";	
		$dados['bolsista']['cidade'] = isset($endereco[4][0]) ? trim($endereco[4][0]) : "";
		$dados['bolsista']['estado'] = isset($endereco[5][0]) ? trim($endereco[5][0]) : "";
	}else{
		$dados['erro'] = true;
		$dados['msg_erro'][] = 'Não foi possível ler dados basicos do histórico (Endereço)';
	}
	
	$texto_curso = trim(string_between_two_string($texto, 'Discente', 'Curso'));
	$curso_array = explode('-', $texto_curso);
	
	if(count($curso_array)>1){
		$curso_instituto = explode('/', $curso_array[0]);
		$dados['curso']['nome'] 	= isset($curso_instituto[0]) ? trim($curso_instituto[0]) : "";
		$dados['curso']['instituto'] = isset($curso_instituto[1]) ? trim($curso_instituto[1]) : "";
		$dados['curso']['cidade'] 	= isset($curso_array[1]) ? trim($curso_array[1]) : "";
		$dados['curso']['grau'] 	= isset($curso_array[2]) ? trim($curso_array[2]) : "";
		$dados['curso']['regime'] 	= isset($curso_array[3]) ? trim($curso_array[3]) : "";
	}else{
		$dados['erro'] = true;
		$dados['msg_erro'][] = 'Não foi possível ler dados basicos do histórico (Curso)';
	}
	/* 
	if(preg_match_all('/(.*?)\/(.*?) \- (.*?) \- (.*?) \- (.*?) \- (.*?)\t/', $texto, $curso )){
		$dados['curso']['nome'] 	= isset($curso[1][0]) ? trim($curso[1][0]) : "";
		$dados['curso']['instituto'] = isset($curso[2][0]) ? trim($curso[2][0]) : "";
		$dados['curso']['cidade'] 	= isset($curso[3][0]) ? trim($curso[3][0]) : "";
		$dados['curso']['grau'] 	= isset($curso[4][0]) ? trim($curso[4][0]) : "";
		$dados['curso']['regime'] 	= isset($curso[5][0]) ? trim($curso[5][0]) : "";
		$dados['curso']['tipo'] 	= isset($curso[6][0]) ? trim($curso[6][0]) : "";
	}else{
		$dados['erro'] = true;
		$dados['msg_erro'][] = 'Não foi possível ler dados basicos do histórico (Curso)';
	}
	*/
	
	if(preg_match_all('/\bIDE: \b(\d{1,2}\.\d{1,2})/', $texto, $ide )){
		$dados['historico']['ide'] = isset($ide[1][0]) ? trim($ide[1][0]) : "";
	}else{
		$dados['erro'] = true;
		$dados['msg_erro'][] = 'Não foi possível ler dados basicos do histórico (IDE)';
	}
	return $dados;
}

function trataBairro($bairro)
{
	if(preg_match_all('/\-(.*?)\t(.*?)\s/', $bairro, $retorno)){
		return trim($retorno[2][0]);
	}
	if(preg_match_all('/(.*?)\t/', $bairro, $retorno)){
		return trim($retorno[1][0]);
	}
	return $bairro;
}

function slug($str)
{
	$str = strtolower(trim($str));
	$str = preg_replace('/[^a-z0-9-]/', '-', $str);
	$str = preg_replace('/-+/', "-", $str);
	return $str;
}

function string_between_two_string($str, $starting_word, $ending_word) 
{ 
    $subtring_start = strpos($str, $starting_word); 
    //Adding the strating index of the strating word to  
    //its length would give its ending index 
    $subtring_start += strlen($starting_word);   
    //Length of our required sub string 
    $size = strpos($str, $ending_word, $subtring_start) - $subtring_start;   
    // Return the substring from the index substring_start of length size  
    return substr($str, $subtring_start, $size);   
}
