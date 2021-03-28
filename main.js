import { indicacaoController } from "./modules/indicacaoController.js";
window.addEventListener("load", event => {
  const api_editais =
    "http://proppg.unilab.edu.br/forms/webservices/editais.php";
  const api_projetos =
    "http://proppg.unilab.edu.br/forms/webservices/projetos.php";
  const api_bancos =
    "http://proppg.unilab.edu.br/forms/json/agencias-fomento.json";
  const lerhistorico = "http://localhost:8000/lerhistorico.php";

  const elementos = {
    sel_edital: document.querySelector("#edital"),
    sel_projetos: document.querySelector("#projeto"),
    edital: {
      anoEdital: document.querySelector("#edital_ano"),
      NEdital: document.querySelector("#edital_num"),
      codEdital: document.querySelector("#edital_texto"),
      idEdital: document.querySelector("#edital_id")
    },
    projeto: {
      idProjeto: document.querySelector("#projeto_id"),
      Processo: document.querySelector("#projeto_processo"),
      siglaInstituto: document.querySelector("#projeto_instituto"),
      nome_area: document.querySelector("#projeto_area"),
      Estado: document.querySelector("#projeto_estado"),
      Situacao: document.querySelector("#projeto_situacao"),
      titulo: document.querySelector("#projeto_titulo"),
      orientador: document.querySelector("#projeto_orientador"),
      dataInicio: document.querySelector("#projeto_inicio"),
      dataFinal: document.querySelector("#projeto_final")
    },
    historico: {
      emissao: document.querySelector("#historico_emissao"),
      ide: document.querySelector("#historico_ide")
    },
    bolsista: {
      nome: document.querySelector("#bolsista_nome"),
      cpf: document.querySelector("#bolsista_cpf"),
      nascimento: document.querySelector("#bolsista_nascimento"),
      nacionalidade: document.querySelector("#bolsista_nacionalidade"),
      endereco: document.querySelector("#bolsista_endereco"),
      cidade: document.querySelector("#bolsista_cidade"),
      bairro: document.querySelector("#bolsista_bairro"),
      estado: document.querySelector("#bolsista_estado")
    },
    curso: {
      nome: document.querySelector("#curso_nome"),
      grau: document.querySelector("#curso_grau"),
      instituto: document.querySelector("#curso_instituto"),
      matricula: document.querySelector("#curso_matricula"),
      regime: document.querySelector("#curso_regime"),
      cidade: document.querySelector("#curso_cidade")
    },
    lattes: {
      atualizacao: document.querySelector("#lattes_atualizacao"),
      cpf: document.querySelector("#lattes_cpf"),
      url: document.querySelector("#lattes_url"),
      nome: document.querySelector("#lattes_nome"),
      nascimento: document.querySelector("#lattes_nascimento"),
      sexo: document.querySelector("#lattes_sexo"),
      telefone: document.querySelector("#lattes_telefone"),
      celular: document.querySelector("#lattes_celular"),
      email_contato: document.querySelector("#lattes_email_contato"),
      email_alternativo: document.querySelector("#lattes_email_alternativo")
    },
    btn_historico: document.querySelector("#btn_historico"),
    arq_historico: document.querySelector("#arquivoHistorico"),
    btn_lattes: document.querySelector("#btn_lattes"),
    arq_lattes: document.querySelector("#arquivoLattes")
  };

  const msg = {
    historico: document.querySelector(".msg-historico")
  };

  const dados = {
    projetos: document.querySelector(".dados-projetos"),
    historico: document.querySelector(".dados-historico"),
    lattes: document.querySelector(".dados-lattes")
  };

  const el_btn_enviar = document.querySelector("#enviar");

  elementos.sel_projetos.disabled = true;
  elementos.btn_historico.disabled = true;
  elementos.btn_lattes.disabled = false; //true

  elementos.sel_edital.addEventListener("change", el => {
    elementos.sel_projetos.innerHTML =
      '<option value="" disabled selected>>>>Selecione</option>';
    elementos.sel_projetos.disabled = true;
    postFormData(api_projetos, { idEdital: el.target.value }).then(function (
      json
    ) {
      if (json.length > 1) {
        //ordenacao dos resultados, crescente, por nome do orientador
        json.sort(function (a, b) {
          var nameA = a.orientador.toLowerCase(),
            nameB = b.orientador.toLowerCase();
          if (nameA < nameB)
            //sort string ascending
            return -1;
          if (nameA > nameB) return 1;
          return 0;
        });
        json.forEach(el => {
          var opt = document.createElement("option");
          opt.value = el.idProjeto;
          opt.text = el.orientador + " (" + el.Processo + ")";
          elementos.sel_projetos.add(opt, null);
        });
        elementos.sel_projetos.disabled = false;
      } else {
        Swal.fire({
          title: "Edital sem projetos cadastrados!",
          text:
            "Esse edital ainda não tem projetos cadastrados ou o banco de dados ainda não foi atualizado",
          icon: "error",
          confirmButtonText: "Ok"
        });
      }
    });
  });

  elementos.sel_projetos.addEventListener("change", el => {
    postFormData(api_projetos, { idProjeto: el.target.value }).then(function (
      json
    ) {
      elementos.btn_historico.setAttribute("disabled", false);
      for (var [key, value] of Object.entries(elementos.edital)) {
        elementos.edital[key].value = json[0][key];
      }
      for (var [key, value] of Object.entries(elementos.projeto)) {
        elementos.projeto[key].value = json[0][key];
      }
      dados.projetos.style.display = "block";
      elementos.btn_historico.disabled = false;
    });
  });

  elementos.btn_historico.addEventListener("click", evt => {
    elementos.arq_historico.click();
  });

  elementos.arq_historico.addEventListener("change", evt => {
    for (var [key, value] of Object.entries(elementos.historico)) {
      elementos.historico[key].value = "carregando...";
    }
    for (var [key, value] of Object.entries(elementos.bolsista)) {
      elementos.bolsista[key].value = "carregando...";
    }
    for (var [key, value] of Object.entries(elementos.curso)) {
      elementos.curso[key].value = "carregando...";
    }
    postFile("lerhistorico.php", elementos.arq_historico, elementos).then(
      data => {
        if (typeof data.erro === "undefined" || data.erro === true) {
          for (var [key, value] of Object.entries(elementos.historico)) {
            elementos.historico[key].value = "";
          }
          for (var [key, value] of Object.entries(elementos.bolsista)) {
            elementos.bolsista[key].value = "";
          }
          for (var [key, value] of Object.entries(elementos.curso)) {
            elementos.curso[key].value = "";
          }
          Swal.fire({
            title: "Arquivo inválido!",
            text:
              "Erro ao tentar ler o arquivo. Tenha certeza de selecionar um Histórico gerado pelo Sigaa",
            icon: "error",
            confirmButtonText: "Ok"
          });
        } else {
          Swal.fire({
            title: "Sucesso!",
            text:
              "Histórico de " + data.bolsista.nome + " carregado com sucesso!",
            icon: "success",
            confirmButtonText: "Ok"
          });
          for (var [key, value] of Object.entries(elementos.historico)) {
            elementos.historico[key].value = data.historico[key];
          }
          for (var [key, value] of Object.entries(elementos.bolsista)) {
            elementos.bolsista[key].value = data.bolsista[key];
          }
          for (var [key, value] of Object.entries(elementos.curso)) {
            elementos.curso[key].value = data.curso[key];
          }
        }
        elementos.btn_lattes.disabled = false;
      }
    );
  });

  elementos.btn_lattes.addEventListener("click", evt => {
    elementos.arq_lattes.click();
  });

  elementos.arq_lattes.addEventListener("change", evt => {
    for (var [key, value] of Object.entries(elementos.lattes)) {
      elementos.lattes[key].value = "carregando...";
    }
    postFile("lerlattes.php", elementos.arq_lattes, elementos).then(data => {
      if (typeof data.erro === "undefined" || data.erro === true) {
        //Se erro
        for (var [key, value] of Object.entries(elementos.lattes)) {
          elementos.lattes[key].value = "";
        }
        Swal.fire({
          title: "Arquivo inválido!",
          text:
            "Erro ao tentar ler o arquivo. Tenha certeza de submeter um arquivo pdf gerado no sistema de currículo lattes",
          icon: "error",
          confirmButtonText: "Ok"
        });
        console.log("Erro ao tentar ler o arquivo");
      } else {
        Swal.fire({
          title: "Sucesso!",
          text: "Currículo de " + data.nome + " carregado com sucesso!",
          icon: "success",
          confirmButtonText: "Ok"
        });
        for (var [key, value] of Object.entries(elementos.lattes)) {
          console.log(data[key]);
          elementos.lattes[key].value = data[key];
        }
      }
    });
  });

  const bancos = fetch(api_bancos)
    .then(response => response.json())
    .then(data => {
      return data;
    })
    .catch(error => console.error(error));

  if (self.fetch) {
    postFormData(api_editais).then(function (json) {
      json.forEach(el => {
        var opt = document.createElement("option");
        opt.value = el.idEdital;
        opt.text = el.CodEdital;
        elementos.sel_edital.add(opt, null);
      });
    });
  } else {
    alert(
      "Para que esse site funcione integralmente é necessário que se utilize um navegador atual"
    );
  }
});

//Upload de arquivos

function postFile(url, input, elementos) {
  const formData = new FormData();

  formData.append(
    "edital",
    elementos.edital.anoEdital.value + "_" + elementos.edital.NEdital.value
  );
  formData.append("processo", elementos.projeto.Processo.value);
  formData.append("arquivo", input.files[0]);

  return fetch(url, {
    method: "POST", // 'GET', 'PUT', 'DELETE', etc.
    body: formData // Coordinate the body type with 'Content-Type'
  })
    .then(response => {
      console.log(response);
      return response.json();
    })
    .catch(error => console.log(error));
}

function getTextSelect(el_select) {
  let index = el_select.options.selectedIndex;
  return el_select.options[index].text;
}

async function postFormData(url, data) {
  try {
    const response = await fetch(url, {
      method: "POST",
      body: new URLSearchParams(data),
      headers: new Headers({
        "Content-type": "application/x-www-form-urlencoded; charset=UTF-8"
      })
    });
    var contentType = response.headers.get("content-type");
    if (contentType && contentType.indexOf("application/json") !== -1) {
      return response.json();
    } else {
      console.log("Erro ao processar o arquivo!");
    }
  } catch (error) {
    return console.error(error);
  }
}
