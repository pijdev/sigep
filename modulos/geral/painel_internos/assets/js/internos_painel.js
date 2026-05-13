// assets/js/internos_painel.js
// JavaScript específico para o painel de internos - Versão corrigida para SPA

// Proteger contra múltiplos carregamentos no SPA
if (typeof window.internosPainelLoaded === "undefined") {
  window.internosPainelLoaded = true;

  // ==================== FUNÇÃO PARA EXIBIR NOME PREFERENCIAL ====================
  function getNomeExibicao(interno) {
    // REGRA CRÍTICA: LGBT sempre usa nome social se disponível
    if (
      interno.eh_lgbt &&
      interno.nome_social &&
      interno.nome_social.trim() !== ""
    ) {
      return {
        nome: interno.nome_social.trim(),
        registro: interno.nome || "",
        usouSocial: true,
        ehLGBT: true,
      };
    }

    // Para demais internos, usa nome social se disponível
    if (interno.nome_social && interno.nome_social.trim() !== "") {
      return {
        nome: interno.nome_social.trim(),
        registro: interno.nome || "",
        usouSocial: true,
        ehLGBT: false,
      };
    }

    // Fallback para nome de registro
    return {
      nome: (interno.nome || "").trim(),
      registro: interno.nome || "",
      usouSocial: false,
      ehLGBT: false,
    };
  }

  // Função para formatar nome completo (principal + registro entre parênteses)
  function formatarNomeCompleto(interno) {
    const nomeInfo = getNomeExibicao(interno);

    if (
      nomeInfo.usouSocial &&
      nomeInfo.registro &&
      nomeInfo.registro.trim() !== ""
    ) {
      return `${nomeInfo.nome} <small style="color: #6c757d; font-size: 0.85em;">(${nomeInfo.registro})</small>`;
    }

    return nomeInfo.nome;
  }

  // ==================== EXEMPLO DE USO: VALIDAÇÃO ANTES DE MOVIMENTAR ====================
  function exemploValidacaoMovimentacao() {
    // Exemplo: Tentar mover interno 12345 para cela A-5 (Convívio)
    const ipens = [12345];
    const galeriaDestino = "A";
    const blocoDestino = "";
    const celaDestino = "5";

    validarMovimentacaoPermitida(
      ipens,
      galeriaDestino,
      blocoDestino,
      celaDestino,
      function (validacao) {
        mostrarAlertaSegurança(validacao);

        if (!validacao.permitido) {
          console.error("🚨 MOVIMENTAÇÃO BLOQUEADA:", validacao.motivo);
          // Aqui você pode impedir a movimentação física
        } else {
          console.log("✅ Movimentação permitida:", validacao.motivo);
          // Aqui você pode prosseguir com a movimentação
        }
      },
    );
  }

  // ==================== FUNÇÃO PARA RASTREAR VIOLAÇÕES ====================
  function registrarTentativaViolacao(ipens, origem, destino, motivo) {
    // Esta função pode registrar em log ou enviar alerta para administração
    const dados = {
      data: new Date().toISOString(),
      ipens: ipens,
      origem: origem,
      destino: destino,
      motivo: motivo,
      usuario: window.usuarioAtual || "Sistema",
      risco: "ALTISSIMO",
    };

    console.warn("🚨 TENTATIVA DE VIOLAÇÃO REGISTRADA:", dados);

    // Aqui você poderia enviar para um endpoint de log
    // $.post('/api/log-violacao', dados);
  }

  // ==================== EXEMPLOS PRÁTICOS DAS REGRAS CRÍTICAS ====================

  // Exemplo 1: Validar movimentação antes de executar
  function exemploValidarMovimentacao() {
    console.log("🔍 EXEMPLO: Validando movimentação crítica...");

    // Tentativa PERIGOSA: Mover interno Convívio para cela com Seguro
    const ipensPerigosos = [12345]; // IPENs de exemplo
    const galeriaDestino = "E"; // Enfermaria
    const celaDestino = "3"; // Cela 3 da enfermaria

    validarMovimentacaoPermitida(
      ipensPerigosos,
      galeriaDestino,
      "", // Sem bloco na enfermaria
      celaDestino,
      function (validacao) {
        mostrarAlertaSegurança(validacao);

        if (!validacao.permitido) {
          console.error("🚨 MOVIMENTAÇÃO BLOQUEADA:", validacao.motivo);
          console.log("Risco:", validacao.risco);
          console.log("Destino tem:", validacao.destino_tem);
          console.log("Mover tem:", validacao.mover_tem);

          // Aqui você impediria a movimentação física
          // Exibir mensagem para o agente
          alert("🚨 MOVIMENTAÇÃO NÃO PERMITIDA!\n\n" + validacao.motivo);
        } else {
          console.log("✅ Movimentação segura:", validacao.motivo);
        }
      },
    );
  }

  // Exemplo 2: Demonstração da regra LGBT (nome social)
  function exemploRegraLGBT() {
    console.log("🏳️ EXEMPLO: Demonstrando regra LGBT...");

    // Interno LGBT COM nome social - deve usar nome social
    const internoLGBTComSocial = {
      ipen: 67890,
      nome: "João Silva",
      nome_social: "Maria Fernanda",
      eh_lgbt: true,
    };

    const nomeExibicao1 = getNomeExibicao(internoLGBTComSocial);
    console.log("LGBT com nome social:", nomeExibicao1.nome);
    // Saída: "Maria Fernanda (João Silva)"

    // Interno LGBT SEM nome social - usa nome de registro
    const internoLGBTSemSocial = {
      ipen: 67891,
      nome: "Carlos Alberto",
      nome_social: "",
      eh_lgbt: true,
    };

    const nomeExibicao2 = getNomeExibicao(internoLGBTSemSocial);
    console.log("LGBT sem nome social:", nomeExibicao2.nome);
    // Saída: "Carlos Alberto"

    // Interno não-LGBT com nome social - usa nome social
    const internoNaoLGBTComSocial = {
      ipen: 67892,
      nome: "Pedro Santos",
      nome_social: "Pedrinho",
      eh_lgbt: false,
    };

    const nomeExibicao3 = getNomeExibicao(internoNaoLGBTComSocial);
    console.log("Não-LGBT com nome social:", nomeExibicao3.nome);
    // Saída: "Pedrinho"
  }

  // Exemplo 3: Função para registrar tentativa de violação
  function registrarTentativaPerigosa() {
    console.log("📝 EXEMPLO: Registrando tentativa perigosa...");

    const tentativa = {
      data: new Date().toISOString(),
      ipens: [12345, 67890],
      origem: "A-5 (Convívio)",
      destino: "E-3 (Enfermaria - Seguro)",
      motivo: "Tentativa de misturar Convívio com Seguro",
      usuario: window.usuarioAtual || "Agente Silva",
      risco: "ALTISSIMO",
      acao: "BLOQUEADO_SISTEMA",
    };

    console.warn("🚨 TENTATIVA REGISTRADA:", tentativa);

    // Enviar para log do servidor (exemplo)
    /*
    $.post('/api/log-violacao', tentativa)
      .done(function() {
        console.log('✅ Tentativa registrada no servidor');
      })
      .fail(function() {
        console.error('❌ Falha ao registrar tentativa');
      });
    */
  }

  // Exemplo 4: Teste de validação em lote
  function exemploValidacaoLote() {
    console.log("🔍 EXEMPLO: Validando movimentação em lote...");

    const movimentacoes = [
      {
        ipens: [11111, 22222],
        origem: "A-1 (Convívio)",
        destino: "A-2 (Convívio)",
        esperado: "PERMITIDO",
      },
      {
        ipens: [33333],
        origem: "B-5 (Seguro)",
        destino: "C-1 (Convívio)",
        esperado: "BLOQUEADO",
      },
      {
        ipens: [44444, 55555],
        origem: "D-3 (Seguro)",
        destino: "E-2 (Enfermaria)",
        esperado: "BLOQUEADO",
      },
    ];

    movimentacoes.forEach((mov, index) => {
      console.log(`\n📍 Teste ${index + 1}: ${mov.origem} → ${mov.destino}`);

      // Extrair dados do destino
      const matchDestino = mov.destino.match(/^([A-Z])-?(\w*)-(\d+)$/);
      if (matchDestino) {
        const [, galeria, bloco, cela] = matchDestino;

        validarMovimentacaoPermitida(
          mov.ipens,
          galeria,
          bloco || "",
          cela,
          function (validacao) {
            const resultado = validacao.permitido ? "✅" : "🚨";
            const status = validacao.permitido ? "PERMITIDO" : "BLOQUEADO";

            console.log(`${resultado} ${status}: ${validacao.motivo}`);

            if (status === mov.esperado) {
              console.log("✅ Validação funcionou corretamente");
            } else {
              console.error("❌ Validação não funcionou como esperado");
            }
          },
        );
      }
    });
  }

  // Exemplo 5: Simulação de cenário crítico da enfermaria
  function exemploCenarioEnfermaria() {
    console.log("🏥 EXEMPLO: Cenário crítico da enfermaria...");

    // Cenário: Enfermaria com múltiplos internos de tipos diferentes
    const enfermaria = {
      cela: "E-1",
      internos_existentes: [
        { ipen: 1001, nome: "José", tipo: "Seguro" },
        { ipen: 1002, nome: "Maria", tipo: "Seguro" },
        { ipen: 1003, nome: "Ana", tipo: "Seguro" },
      ],
    };

    console.log("🏥 Enfermaria E-1 atual:");
    enfermaria.internos_existentes.forEach((i) => {
      console.log(`  - ${i.tipo}: ${i.nome} (IPEN: ${i.ipen})`);
    });

    // Tentativa 1: Mover interno Convívio para enfermaria com Seguros
    console.log("\n🚨 TENTATIVA 1: Convívio → Enfermaria (com Seguros)");
    validarMovimentacaoPermitida([2001], "E", "", "1", function (validacao) {
      console.log(
        "Resultado:",
        validacao.permitido ? "PERMITIDO" : "BLOQUEADO",
      );
      console.log("Motivo:", validacao.motivo);
    });

    // Tentativa 2: Mover interno Seguro para cela vazia (deve permitir)
    console.log("\n✅ TENTATIVA 2: Seguro → Cela vazia");
    validarMovimentacaoPermitida([2002], "A", "", "10", function (validacao) {
      console.log(
        "Resultado:",
        validacao.permitido ? "PERMITIDO" : "BLOQUEADO",
      );
      console.log("Motivo:", validacao.motivo);
    });
  }

  // ==================== EXEMPLOS DE GALERIAS ESPECIAIS ====================
  function exemploCenarioTriagem() {
    console.log("🏥 CENÁRIO: Triagem com diferentes tipos");

    // Triagem T-5 atual: 1 Convívio, 1 Seguro
    const triagem = {
      galeria: "T",
      cela: "5",
      internos_existentes: [
        { ipen: 3001, nome: "Carlos", tipo: "Cela" }, // Convívio
        { ipen: 3002, nome: "Pedro", tipo: "Seguro" }, // Seguro
      ],
    };

    console.log("🏥 Triagem T-5 atual:");
    triagem.internos_existentes.forEach((i) => {
      console.log(`  - ${i.tipo}: ${i.nome} (IPEN: ${i.ipen})`);
    });

    // Tentativa 1: Mover outro Convívio para Triagem (DEVE ALERTAR MISTURA)
    console.log(
      "\n🚨 TENTATIVA 1: Convívio → Triagem (já tem Convívio + Seguro)",
    );
    validarMovimentacaoPermitida([3003], "T", "", "5", function (validacao) {
      console.log(
        "Resultado:",
        validacao.permitido ? "PERMITIDO" : "BLOQUEADO",
      );
      console.log("Motivo:", validacao.motivo);
      if (validacao.galeria_tipo) {
        console.log("Galeria:", validacao.galeria_tipo);
        console.log("Composição final:", validacao.composicao_final);
        console.log("Nível risco:", validacao.nivel_risco);
      }
    });

    // Tentativa 2: Mover interno da rua para Triagem (DEVE PERMITIR)
    console.log("\n✅ TENTATIVA 2: Interno da rua → Triagem");
    validarMovimentacaoPermitida([3004], "T", "", "5", function (validacao) {
      console.log(
        "Resultado:",
        validacao.permitido ? "PERMITIDO" : "BLOQUEADO",
      );
      console.log("Motivo:", validacao.motivo);
    });
  }

  function exemploCenarioIsolamento() {
    console.log("🔒 CENÁRIO: Isolamento com interno novo");

    // Isolamento G-2 vazio
    console.log("🔒 Isolamento G-2 está vazio");

    // Tentativa: Mover interno direto da rua para isolamento (DEVE PERMITIR)
    console.log("\n✅ TENTATIVA: Interno da rua → Isolamento");
    validarMovimentacaoPermitida([4001], "G", "", "2", function (validacao) {
      console.log(
        "Resultado:",
        validacao.permitido ? "PERMITIDO" : "BLOQUEADO",
      );
      console.log("Motivo:", validacao.motivo);
      if (validacao.galeria_tipo) {
        console.log("Galeria:", validacao.galeria_tipo);
        console.log("Composição final:", validacao.composicao_final);
      }
    });
  }

  function exemploCenarioMisturaPerigosa() {
    console.log("⚠️ CENÁRIO: Mistura perigosa em Enfermaria");

    // Enfermaria F-3: 2 Seguros
    const enfermaria = {
      galeria: "F",
      cela: "3",
      internos_existentes: [
        { ipen: 5001, nome: "João", tipo: "Seguro" },
        { ipen: 5002, nome: "Luís", tipo: "Seguro" },
      ],
    };

    console.log("🏥 Enfermaria F-3 atual:");
    enfermaria.internos_existentes.forEach((i) => {
      console.log(`  - ${i.tipo}: ${i.nome} (IPEN: ${i.ipen})`);
    });

    // Tentativa: Mover interno do Convívio para Enfermaria com Seguros (DEVE BLOQUEAR)
    console.log(
      "\n🚨 TENTATIVA: Convívio → Enfermaria (com Seguros existentes)",
    );
    validarMovimentacaoPermitida([5003], "F", "", "3", function (validacao) {
      console.log(
        "Resultado:",
        validacao.permitido ? "PERMITIDO" : "BLOQUEADO",
      );
      console.log("Motivo:", validacao.motivo);
      if (validacao.galeria_tipo) {
        console.log("Galeria:", validacao.galeria_tipo);
        console.log("Composição atual:", validacao.composicao_atual);
        console.log("Internos a mover:", validacao.internos_mover);
        console.log("Composição final:", validacao.composicao_final);
        console.log("Nível risco:", validacao.nivel_risco);
        console.log("Alerta mistura:", validacao.alerta_mistura);
        if (validacao.detalhe_alerta) {
          console.log("Detalhe do alerta:", validacao.detalhe_alerta);
        }
      }
    });
  }

  // ==================== EXEMPLOS DE GALERIAS ESPECIAIS ====================
  function exemploCenarioSemiAberto() {
    console.log("🏥 CENÁRIO: Semi-Aberto com diferentes tipos");

    // Semi-Aberto SA-5: 2 Convívio, 1 Seguro
    const semiAberto = {
      galeria: "S",
      bloco: "A",
      cela: "5",
      internos_existentes: [
        { ipen: 6001, nome: "Carlos", tipo: "Cela" }, // Convívio
        { ipen: 6002, nome: "Pedro", tipo: "Cela" }, // Convívio
        { ipen: 6003, nome: "Antônio", tipo: "Seguro" }, // Seguro
      ],
    };

    console.log("🏥 Semi-Aberto SA-5 atual:");
    semiAberto.internos_existentes.forEach((i) => {
      console.log(`  - ${i.tipo}: ${i.nome} (IPEN: ${i.ipen})`);
    });

    // Tentativa 1: Mover outro Convívio para Semi-Aberto (DEVE ALERTAR MISTURA)
    console.log(
      "\n🚨 TENTATIVA 1: Convívio → Semi-Aberto (já tem Convívio + Seguro)",
    );
    validarMovimentacaoPermitida([6004], "S", "A", "5", function (validacao) {
      console.log(
        "Resultado:",
        validacao.permitido ? "PERMITIDO" : "BLOQUEADO",
      );
      console.log("Motivo:", validacao.motivo);
      if (validacao.galeria_tipo) {
        console.log("Galeria:", validacao.galeria_tipo);
        console.log("Composição final:", validacao.composicao_final);
        console.log("Nível risco:", validacao.nivel_risco);
      }
    });

    // Tentativa 2: Mover interno Seguro para Semi-Aberto com Convívios (DEVE BLOQUEAR)
    console.log(
      "\n🚨 TENTATIVA 2: Seguro → Semi-Aberto (com Convívios existentes)",
    );
    validarMovimentacaoPermitida([6005], "S", "A", "5", function (validacao) {
      console.log(
        "Resultado:",
        validacao.permitido ? "PERMITIDO" : "BLOQUEADO",
      );
      console.log("Motivo:", validacao.motivo);
      if (validacao.galeria_tipo) {
        console.log("Galeria:", validacao.galeria_tipo);
        console.log("Composição final:", validacao.composicao_final);
        console.log("Nível risco:", validacao.nivel_risco);
      }
    });

    // Tentativa 3: Mover interno do Semi-Aberto B para vazio (DEVE PERMITIR)
    console.log("\n✅ TENTATIVA 3: Semi-Aberto B → Cela vazia");
    validarMovimentacaoPermitida([6006], "S", "B", "3", function (validacao) {
      console.log(
        "Resultado:",
        validacao.permitido ? "PERMITIDO" : "BLOQUEADO",
      );
      console.log("Motivo:", validacao.motivo);
    });
  }

  // Adicionar funções ao escopo global para teste no console
  window.exemplosSIGEP = {
    validarMovimentacao: exemploValidarMovimentacao,
    regraLGBT: exemploRegraLGBT,
    registrarTentativa: registrarTentativaPerigosa,
    validarLote: exemploValidacaoLote,
    cenarioEnfermaria: exemploCenarioEnfermaria,
    cenarioTriagem: exemploCenarioTriagem,
    cenarioIsolamento: exemploCenarioIsolamento,
    cenarioMisturaPerigosa: exemploCenarioMisturaPerigosa,
    cenarioSemiAberto: exemploCenarioSemiAberto,
  };

  // ==================== FUNÇÃO CRÍTICA: VALIDAR CONVÍVIO ====================
  function validarMovimentacaoPermitida(
    ipens,
    galeriaDestino,
    blocoDestino,
    celaDestino,
    callback,
  ) {
    // Garantir que ipens seja array
    const ipensArray = Array.isArray(ipens) ? ipens : [ipens];

    $.ajax({
      url: "/modulos/geral/painel_internos/internos_painel_controller.php",
      method: "POST",
      data: {
        action: "validar_movimentacao",
        ipens: ipensArray,
        galeria_destino: galeriaDestino,
        bloco_destino: blocoDestino,
        cela_destino: celaDestino,
      },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          callback(response);
        } else {
          console.error("Erro na validação:", response.error);
          callback({
            permitido: false,
            motivo: "Erro na validação: " + response.error,
            risco: "ALTO",
          });
        }
      },
      error: function (xhr, status, error) {
        console.error("Falha na validação:", error);
        callback({
          permitido: false,
          motivo: "Falha na comunicação com servidor",
          risco: "ALTO",
        });
      },
    });
  }

  // Função para mostrar alerta de segurança
  function mostrarAlertaSegurança(validacao) {
    const tipoAlerta = validacao.permitido ? "success" : "danger";
    const icone = validacao.permitido ? "✅" : "🚨";
    const titulo = validacao.permitido
      ? "Movimentação Permitida"
      : "MOVIMENTAÇÃO BLOQUEADA";

    // Verificar se é galeria especial para mostrar detalhes
    const ehGaleriaEspecial =
      validacao.galeria_tipo &&
      ["Enfermaria", "Isolamento", "Triagem/Castigo"].includes(
        validacao.galeria_tipo,
      );

    let detalhesHtml = "";
    let composicaoHtml = "";

    if (ehGaleriaEspecial) {
      // Composição atual
      if (validacao.composicao_atual && validacao.composicao_atual.total > 0) {
        composicaoHtml += `
          <div class="row mb-3">
            <div class="col-12">
              <h6><i class="fas fa-info-circle"></i> Composição Atual em ${validacao.galeria_tipo}:</h6>
              <div class="ml-3">
                ${validacao.composicao_atual.convivio > 0 ? `<span class="badge badge-cela mr-2">Convívio: ${validacao.composicao_atual.convivio}</span>` : ""}
                ${validacao.composicao_atual.seguro > 0 ? `<span class="badge badge-seguro mr-2">Seguro: ${validacao.composicao_atual.seguro}</span>` : ""}
                ${validacao.composicao_atual.outros > 0 ? `<span class="badge badge-padrao mr-2">Outros: ${validacao.composicao_atual.outros}</span>` : ""}
                <small class="text-muted ml-2">(Total: ${validacao.composicao_atual.total})</small>
              </div>
            </div>
          </div>
        `;
      }

      // Internos que serão movidos
      if (validacao.internos_mover && validacao.internos_mover.total > 0) {
        composicaoHtml += `
          <div class="row mb-3">
            <div class="col-12">
              <h6><i class="fas fa-user-plus"></i> Internos a serem movidos:</h6>
              <div class="ml-3">
                ${validacao.internos_mover.convivio > 0 ? `<span class="badge badge-cela mr-2">Convívio: ${validacao.internos_mover.convivio}</span>` : ""}
                ${validacao.internos_mover.seguro > 0 ? `<span class="badge badge-seguro mr-2">Seguro: ${validacao.internos_mover.seguro}</span>` : ""}
                ${validacao.internos_mover.outros > 0 ? `<span class="badge badge-padrao mr-2">Outros: ${validacao.internos_mover.outros}</span>` : ""}
                <small class="text-muted ml-2">(Total: ${validacao.internos_mover.total})</small>
              </div>
            </div>
          </div>
        `;
      }

      // Composição final após movimentação
      if (validacao.composicao_final) {
        const riscoClass = validacao.alerta_mistura
          ? "alert-danger"
          : validacao.nivel_risco === "CRÍTICO"
            ? "alert-danger"
            : validacao.nivel_risco === "ALTO"
              ? "alert-warning"
              : "alert-info";

        composicaoHtml += `
          <div class="row mb-3">
            <div class="col-12">
              <h6><i class="fas fa-chart-pie"></i> Composição Final em ${validacao.galeria_tipo}:</h6>
              <div class="ml-3">
                ${validacao.composicao_final.convivio > 0 ? `<span class="badge badge-cela mr-2">Convívio: ${validacao.composicao_final.convivio}</span>` : ""}
                ${validacao.composicao_final.seguro > 0 ? `<span class="badge badge-seguro mr-2">Seguro: ${validacao.composicao_final.seguro}</span>` : ""}
                ${validacao.composicao_final.outros > 0 ? `<span class="badge badge-padrao mr-2">Outros: ${validacao.composicao_final.outros}</span>` : ""}
                <small class="text-muted ml-2">(Total: ${validacao.composicao_final.total})</small>
              </div>
              <div class="mt-2">
                <span class="badge ${riscoClass}">NÍVEL DE RISCO: ${validacao.nivel_risco}</span>
                ${validacao.alerta_mistura ? `<div class="mt-2"><small class="text-danger">${validacao.detalhe_alerta}</small></div>` : ""}
              </div>
            </div>
          </div>
        `;
      }

      detalhesHtml = `
        <div class="card card-outline card-${validacao.permitido ? "success" : "danger"} mb-3">
          <div class="card-header">
            <h6 class="card-title mb-0">
              <i class="fas fa-${validacao.permitido ? "check-circle" : "exclamation-triangle"}"></i>
              Análise Detalhada - ${validacao.galeria_tipo}
            </h6>
          </div>
          <div class="card-body p-2">
            ${composicaoHtml}
          </div>
        </div>
      `;
    } else {
      // Lógica original para galerias normais
      detalhesHtml = `
        ${
          validacao.destino_tem && validacao.mover_tem
            ? `
          <hr>
          <small class="text-muted">
            Destino tem: <strong>${validacao.destino_tem}</strong> |
            Mover tem: <strong>${validacao.mover_tem}</strong>
          </small>
        `
            : ""
        }
      `;
    }

    const alertHtml = `
      <div class="alert alert-${tipoAlerta} alert-dismissible fade show" role="alert" style="border-left: 4px solid ${validacao.permitido ? "#28a745" : "#dc3545"};">
        <h5 class="alert-heading">
          <strong>${icone} ${titulo}</strong>
        </h5>
        <p class="mb-2">${validacao.motivo}</p>
        ${validacao.risco ? `<small class="text-muted">Risco: ${validacao.risco}</small>` : ""}
        ${detalhesHtml}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    `;

    // Inserir alerta no topo do offcanvas
    const offcanvasBody = document.getElementById("offcanvasBodyCela");
    offcanvasBody.insertAdjacentHTML("afterbegin", alertHtml);

    // Auto-remover após 15 segundos se for permitido
    if (validacao.permitido) {
      setTimeout(() => {
        const alert = offcanvasBody.querySelector(".alert");
        if (alert) alert.remove();
      }, 15000);
    }
  }

  // ==================== FUNÇÕES DO OFFCANVAS ====================
  // Função para formatar título da cela
  function formatarTituloCela(galeria, bloco, cela) {
    if (!galeria || !cela) return "Cela Indefinida";

    let titulo = "";
    if (galeria === "S") {
      titulo = `Semi-Aberto ${bloco || ""}-${cela}`;
    } else {
      titulo = `${galeria}${bloco || ""}-${cela}`;
    }

    return `Cela ${titulo}`;
  }

  // Função para renderizar conteúdo do offcanvas
  function renderOffcanvasContent(json, mostrarTodasMudancas) {
    document.getElementById("offcanvasBodyCela").innerHTML = "Function called";
    const ele = json.eletronicos || {};
    const labelsEle = {
      Chaleira: "Chaleira",
      "Maquina Cabelo": "Máquina de Cabelo",
      Radio: "Rádio",
      TV: "TV",
      Ventilador: "Ventilador",
      Bola: "Bola",
      Banqueta: "Banqueta",
      Chuveiro: "Chuveiro",
      Outro: "Outro",
    };

    let htmlEletro = "";
    for (const [key, label] of Object.entries(labelsEle)) {
      let qtd = 0;
      let items = [];
      const val = ele[key];
      if (Array.isArray(val)) {
        items = val;
        qtd = items.length;
      } else if (typeof val === "object" && val !== null && "length" in val) {
        // Handle array-like objects
        items = Array.from(val);
        qtd = items.length;
      } else if (typeof val === "number") {
        qtd = val;
      } else {
        qtd = 0;
      }
      // Ensure qtd is a number
      qtd = Number(qtd) || 0;
      const badgeClass = qtd > 0 ? "bg-primary" : "bg-secondary";
      const tooltipAttrs =
        qtd > 0
          ? 'data-bs-toggle="popover" data-bs-content="Funciona" data-bs-trigger="click"'
          : "";
      const cursor = qtd > 0 ? "pointer" : "default";
      htmlEletro += `<span class="fw-bold d-block mb-1">${label}: <button type="button" class="badge rounded-pill ${badgeClass} eletronico-badge" style="cursor: ${cursor};" ${tooltipAttrs}>${qtd}</button></span>`;
    }

    let htmlInternos = "";
    const hoje = new Date();
    hoje.setHours(0, 0, 0, 0);

    (json.internos || []).forEach((interno) => {
      const nomeInfo = getNomeExibicao(interno);
      const nomeExibicao = formatarNomeCompleto(interno);
      const temAntigaCela =
        interno.movimento && interno.movimento.antiga_cela_label;

      // Verificar se o movimento é recente (48 horas) ou se deve mostrar todos
      const dataMovimento =
        interno.movimento && interno.movimento.data_alteracao
          ? new Date(interno.movimento.data_alteracao)
          : null;
      const mostrarMovimento =
        mostrarTodasMudancas || (dataMovimento && dataMovimento >= hoje);

      htmlInternos +=
        '<li class="' +
        (temAntigaCela && mostrarMovimento ? "text-info fw-bold" : "") +
        '">';
      htmlInternos += interno.ipen + " - " + nomeExibicao;
      if (temAntigaCela && mostrarMovimento) {
        const dataMov = dataMovimento
          ? dataMovimento.toLocaleDateString("pt-BR") +
            " " +
            dataMovimento.toLocaleTimeString("pt-BR", {
              hour: "2-digit",
              minute: "2-digit",
            })
          : "";
        htmlInternos +=
          '<br><span class="text-warning"><i class="fas fa-arrow-right-arrow-left fa-fw"></i> Antiga cela: ' +
          interno.movimento.antiga_cela_label +
          (dataMov ? " <small>(" + dataMov + ")</small>" : "") +
          "</span>";
      }
      htmlInternos += "</li>";
    });

    // Renderizar histórico de movimentações unificado
    const htmlMovimentacoes = renderHistoricoMovimentacoes(json);

    // Renderizar medidas disciplinares
    const htmlMedidas = renderMedidas(json);

    // Montar HTML final
    const htmlFinal = `
        <div class="offcanvas-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary"><i class="fas fa-tv"></i> Eletrônicos</h6>
                    <div class="mb-3">${htmlEletro}</div>

                    <h6 class="text-info"><i class="fas fa-users"></i> Internos (${json.internos?.length || 0})</h6>
                    <ul class="list-unstyled small">${htmlInternos}</ul>
                </div>

                <div class="col-md-6">
                    <h6 class="text-success"><i class="fas fa-exchange-alt"></i> Movimentações</h6>
                    ${htmlMovimentacoes}

                    <h6 class="text-warning"><i class="fas fa-gavel"></i> Medidas Disciplinares</h6>
                    ${htmlMedidas}
                </div>
            </div>
        </div>
    `;

    document.getElementById("offcanvasBodyCela").innerHTML = htmlFinal;

    // Inicializar tooltips Bootstrap nos badges
    $('[data-bs-toggle="popover"]').popover();
  }

  // Função para renderizar movimentações
  function renderMovimentacoes(json, mostrarTodasMudancas) {
    const saidas = json.saidas || [];
    const entradas = json.entradas || [];
    const hoje = new Date();
    hoje.setHours(0, 0, 0, 0);

    // Processar entradas e saídas
    const htmlEntradas = processarMovimentacoes(
      entradas,
      hoje,
      mostrarTodasMudancas,
      "entrada",
    );
    const htmlSaidas = processarMovimentacoes(
      saidas,
      hoje,
      mostrarTodasMudancas,
      "saida",
    );

    return `
        <div class="mb-3">
            <div class="mb-2">
                <small class="text-muted">Entradas:</small>
                ${htmlEntradas}
            </div>
            <div>
                <small class="text-muted">Saídas:</small>
                ${htmlSaidas}
            </div>
        </div>
    `;
  }

  // Função para processar movimentações (entradas/saídas)
  function processarMovimentacoes(
    movimentacoes,
    hoje,
    mostrarTodasMudancas,
    tipo,
  ) {
    if (movimentacoes.length === 0) {
      return `<p class="small text-muted mb-0"><i class="fas fa-inbox fa-fw"></i> Nenhuma ${tipo} registrada</p>`;
    }

    const mudancasHoje = mostrarTodasMudancas
      ? movimentacoes
      : movimentacoes.filter((m) => {
          const data = new Date(m["data_" + tipo]);
          return data >= hoje;
        });

    if (mudancasHoje.length === 0 && !mostrarTodasMudancas) {
      return `<p class="small text-muted mb-0"><i class="fas fa-history fa-fw"></i> Nenhuma ${tipo} recente</p>`;
    }

    let html = "";
    mudancasHoje.forEach((m) => {
      const data = new Date(m[`data_${tipo}`]);
      const dataFormatada =
        data.toLocaleDateString("pt-BR") +
        " " +
        data.toLocaleTimeString("pt-BR", {
          hour: "2-digit",
          minute: "2-digit",
        });
      const local = tipo === "entrada" ? m.origem : m.destino;
      const localText =
        local && local !== "Não informado"
          ? `<span class="text-muted">${tipo === "entrada" ? "←" : "→"} ${local}</span>`
          : "";
      const hojeClass = data >= hoje ? `${tipo}-hoje` : "";

      html += `
            <div class="movimentacao-item ${hojeClass} p-1 border-bottom small">
                <div class="d-flex justify-content-between">
                    <div>
                        <strong>${m.ipen}</strong> - ${m.nome || ""} ${localText}
                        <br><small class="text-muted">${dataFormatada}</small>
                    </div>
                    ${data >= hoje ? `<span class="badge ${tipo === "entrada" ? "bg-info" : "bg-success"} badge-hoje">Hoje</span>` : ""}
                </div>
            </div>`;
    });

    return html;
  }

  // Função para renderizar medidas disciplinares
  function renderMedidas(json) {
    const medidas = json.medidas_disciplinares || [];
    const hoje = new Date();
    hoje.setHours(0, 0, 0, 0);

    if (medidas.length === 0) {
      return '<p class="small text-muted mb-0"><i class="fas fa-check-circle fa-fw"></i> Nenhuma MD ativa</p>';
    }

    let html = "";
    medidas.forEach((md) => {
      const nomeInfo = getNomeExibicao(md);
      const nomeExibicao = formatarNomeCompleto(md);
      const dataFim = new Date(md.data_fim);
      const diasRestantes = Math.ceil((dataFim - hoje) / (1000 * 60 * 60 * 24));
      const vencendoClass =
        diasRestantes <= 2 ? "text-danger font-weight-bold" : "";
      const vencendoIcon =
        diasRestantes <= 2 ? '<i class="fas fa-exclamation-triangle"></i>' : "";

      html += `
                <li class="mb-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${nomeExibicao}</strong>
                            <small class="text-muted d-block">IPEN: ${md.ipen}</small>
                        </div>
                        <div>
                            <small><strong>Local:</strong> ${md.local_castigo}</small>
                        </div>
                        <div class="text-right">
                            <small class="${vencendoClass}">${diasRestantes}d</small>
                            <br><small class="text-muted">${dataFim.toLocaleDateString("pt-BR")}</small>
                        </div>
                    </div>
                </li>`;
    });

    return html;
  }

  // Função para mostrar modal de eletrônicos
  function showEletronicosModal(tipo, galeria, bloco, ala, cela) {
    // Fetch details
    const fd = new FormData();
    fd.append("action", "get_eletronicos_detalhes");
    fd.append("tipo", tipo);
    fd.append("galeria", galeria);
    fd.append("bloco", bloco);
    fd.append("ala", ala);
    fd.append("cela", cela);

    fetch("modulos/geral/painel_internos/internos_painel_controller.php", {
      method: "POST",
      body: fd,
    })
      .then((res) => res.json())
      .then((json) => {
        if (json.success) {
          const items = json.itens;
          // Sort by name
          items.sort((a, b) => a.nome.localeCompare(b.nome));

          const labelsEle = {
            Chaleira: "Chaleira",
            "Maquina Cabelo": "Máquina de Cabelo",
            Radio: "Rádio",
            TV: "TV",
            Ventilador: "Ventilador",
            Bola: "Bola",
            Banqueta: "Banqueta",
            Outro: "Outro",
          };

          let html = `<h6 class="text-center mb-3">${labelsEle[tipo]}</h6><ul class="list-group">`;
          items.forEach((item) => {
            const dataFormatada = new Date(
              item.data_entrada,
            ).toLocaleDateString("pt-BR");
            html += `<li class="list-group-item list-group-item-action p-2" onclick="window.loadPage('paginas/internos_eletronicos_gestao.php?ipen=${item.ipen}')">
                        <strong>${item.ipen}</strong> - ${item.nome}<br><small class="text-muted">Entrada: ${dataFormatada}</small>
                    </li>`;
          });
          html += "</ul>";
          html += `<div class="mt-3 text-center"><button class="btn btn-primary btn-sm" onclick="window.loadPage('paginas/internos_eletronicos_gestao.php?galeria=${galeria}&bloco=${bloco}&ala=${ala}&cela=${cela}')">Ver todos os eletrônicos da cela</button></div>`;

          document.getElementById("eletronicosModalBody").innerHTML = html;
          document.getElementById("eletronicosModal").style.display = "block";
        } else {
          alert("Erro ao carregar detalhes");
        }
      })
      .catch((error) => {
        console.error("Erro ao buscar detalhes:", error);
        alert("Erro ao carregar detalhes");
      });
  }

  // Função para carregar mais dados (histórico completo)
  window.carregarMaisDados = function () {
    if (window.ultimaCelaParams) {
      const { galeria, ala, cela, blocoOpcional } = window.ultimaCelaParams;
      window.abrirCela(galeria, ala, cela, blocoOpcional, true);
    }
  };

  // Função para fechar offcanvas
  window.fecharOffcanvasCela = function () {
    const overlay = document.getElementById("overlayOffcanvasCela");
    const offcanvas = document.getElementById("offcanvasCela");

    if (overlay) {
      overlay.classList.remove("show");
    }
    if (offcanvas) {
      offcanvas.classList.remove("show");
    }
  };

  // Listener para fechar offcanvas ao clicar no overlay
  document.addEventListener("DOMContentLoaded", function () {
    const overlay = document.getElementById("overlayOffcanvasCela");
    if (overlay) {
      overlay.addEventListener("click", window.fecharOffcanvasCela);
    }

    // Listener para fechar modal de eletrônicos
    const modal = document.getElementById("eletronicosModal");
    if (modal) {
      modal.addEventListener("click", function (e) {
        if (e.target === modal) {
          modal.style.display = "none";
        }
      });
    }

    // Listener para tecla ESC
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        window.fecharOffcanvasCela();
      }
    });
  });

  window.pageTitle = "Painel de Internos";

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") window.fecharOffcanvasCela();
  });

  // ==================== BUSCA DE INTERNOS ====================
  if (typeof timeoutBusca === "undefined") {
    window.timeoutBusca = null;
  }

  // Declarar variáveis dentro do escopo protegido
  const inputBusca = document.getElementById("busca_interno");
  const resultadosBusca = document.getElementById("busca_resultados");

  // Verificar se os elementos existem antes de adicionar event listeners
  if (inputBusca && resultadosBusca) {
    inputBusca.addEventListener("input", async (e) => {
      clearTimeout(window.timeoutBusca);
      const termo = e.target.value.trim();

      if (termo.length < 2) {
        resultadosBusca.style.display = "none";
        inputBusca.classList.remove("busca-loading");
        return;
      }

      // Adicionar loading ao campo de busca
      inputBusca.classList.add("busca-loading");

      window.timeoutBusca = setTimeout(async () => {
        try {
          const fd = new FormData();
          fd.append("action", "search_interno");
          fd.append("termo", termo);

          const res = await fetch(
            "modulos/geral/painel_internos/internos_painel_logica.php",
            {
              method: "POST",
              body: fd,
            },
          );
          const json = await res.json();

          if (json.success && json.resultados.length > 0) {
            let html = "";

            json.resultados.forEach((interno) => {
              const nomeInfo = getNomeExibicao(interno);
              const nomeExibicao = formatarNomeCompleto(interno);
              const display = `${nomeExibicao} (IPEN: ${interno.ipen})`;
              const galeriaDisplay =
                interno.galeria === "S" && interno.bloco
                  ? `Semi-Aberto ${interno.bloco}`
                  : interno.galeria || "";
              const celaLabel = galeriaDisplay
                ? galeriaDisplay + "-" + interno.res
                : interno.galeria + "/" + interno.res;

              html += `
                        <div class="busca-resultado-item" onclick="buscarInternoCela('${galeriaDisplay}', '${(interno.ala || "").replace(/'/g, "\\'")}', '${interno.res}', '${interno.ipen}')">
                            <div class="busca-resultado-nome">${display}</div>
                            <div class="busca-resultado-info">
                                <span><strong>Cela:</strong> ${celaLabel}</span>
                            </div>
                        </div>
                    `;
            });

            resultadosBusca.innerHTML = html;
            resultadosBusca.style.display = "block";
          } else {
            resultadosBusca.innerHTML =
              '<div class="text-center p-3 text-muted">Nenhum interno encontrado.</div>';
            resultadosBusca.style.display = "block";
          }
        } catch (e) {
          console.error("Erro na busca:", e);
          resultadosBusca.innerHTML =
            '<div class="text-center p-3 text-danger">Erro ao buscar internos.</div>';
          resultadosBusca.style.display = "block";
        } finally {
          inputBusca.classList.remove("busca-loading");
        }
      }, 300);
    });

    // Adicionar suporte à tecla ESC para fechar offcanvas
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        const offcanvas = document.getElementById("offcanvasCela");
        if (offcanvas && offcanvas.classList.contains("control-sidebar-open")) {
          window.fecharOffcanvasCela();
        }
      }
    });
  } // Fim do if (inputBusca && resultadosBusca)

  // ==================== FUNÇÕES DA CELA ====================
  window.abrirCela = async (
    galeria,
    ala,
    cela,
    blocoOpcional,
    mostrarTodasMudancas = false,
  ) => {
    const overlay = document.getElementById("overlayOffcanvasCela");
    const offcanvas = document.getElementById("offcanvasCela");
    const bodyContent = document.getElementById("offcanvasBodyCela");

    // Mostrar overlay e offcanvas usando DOM manipulation
    if (overlay) {
      overlay.style.display = "block";
    }

    if (offcanvas) {
      offcanvas.style.display = "block";
      offcanvas.style.right = "0";
      offcanvas.classList.add("control-sidebar-open");
    }

    // Mostrar loading
    bodyContent.innerHTML = `
        <div style="text-align: center; padding: 30px;">
            <div class="loading-spinner"></div>
            <p style="margin-top: 15px;">Carregando dados...</p>
        </div>
    `;

    // Salvar parâmetros para uso posterior
    window.ultimaCelaParams = { galeria, ala, cela, blocoOpcional };

    try {
      const fd = new FormData();
      fd.append("action", "fetch_cela_internos");
      fd.append("galeria", galeria);
      fd.append("bloco", blocoOpcional || "");
      fd.append("ala", ala);
      fd.append("cela", cela);
      fd.append("mostrar_todas_mudancas", mostrarTodasMudancas ? "1" : "0");

      const res = await fetch(
        "modulos/geral/painel_internos/internos_painel_controller.php",
        {
          method: "POST",
          body: fd,
        },
      );
      const json = await res.json();

      if (json.success) {
        renderDetalhesCela(json);
      } else {
        throw new Error(json.error || "Erro ao carregar detalhes");
      }
    } catch (e) {
      console.error("Erro ao carregar detalhes da cela:", e);
      bodyContent.innerHTML = `
            <div class="alert alert-danger m-3">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Erro ao carregar dados da cela.
            </div>
        `;
    }
  };

  window.fecharOffcanvasCela = () => {
    const overlay = document.getElementById("overlayOffcanvasCela");
    const offcanvas = document.getElementById("offcanvasCela");

    // Esconder overlay
    if (overlay) {
      overlay.style.display = "none";
    }

    // Esconder offcanvas
    if (offcanvas) {
      offcanvas.classList.remove("control-sidebar-open");
      offcanvas.style.display = "none";
    }

    // Não usar AdminLTE ControlSidebar - usar apenas DOM manipulation
    // Isso evita o erro "a[t] is not a function"
  };

  window.buscarInternoCela = (galeria, ala, cela, ipen) => {
    // Fechar offcanvas atual se estiver aberto
    window.fecharOffcanvasCela();

    // Abrir offcanvas com o novo interno
    window.abrirCela(galeria, ala, cela, "", true);
  };

  // ==================== RENDERIZAÇÃO ====================
  function renderDetalhesCela(dados) {
    const bodyContent = document.getElementById("offcanvasBodyCela");

    // Montar label da cela
    const celaLabel = dados.bloco
      ? `${dados.galeria}${dados.bloco}-${dados.cela}`
      : `${dados.galeria}-${dados.cela}`;

    // Contar itens
    const totalInternos = dados.internos ? dados.internos.length : 0;
    const totalMDs = dados.medidas_disciplinares
      ? dados.medidas_disciplinares.length
      : 0;

    // Contar eletrônicos
    const eletronicosCount = dados.eletronicos || {};
    const tv = eletronicosCount["TV"] || 0;
    const radio = eletronicosCount["Radio"] || 0;
    const chaleira = eletronicosCount["Chaleira"] || 0;
    const ventilador = eletronicosCount["Ventilador"] || 0;
    const chuveiro = eletronicosCount["Chuveiro"] || 0;
    const violao = eletronicosCount["Bola"] || 0;
    const banqueta = eletronicosCount["Banqueta"] || 0;
    const extensao = eletronicosCount["Extensão"] || 0;
    const maqCabelo = eletronicosCount["Maquina Cabelo"] || 0;
    const outros = eletronicosCount["Outro"] || 0;

    let html = `
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="m-0">${celaLabel}</h3>
        </div>

        <div class="row">
            <!-- Coluna Esquerda: Itens na Cela -->
            <div class="col-5 border-right border-white pr-3">
                <h5 class="mb-3 text-center">Itens na Cela</h5>
                <div class="item-row"><span>Televisão:</span> <span class="badge-custom bg-blue-light cursor-pointer" onclick="abrirModalItens('TV', ${tv}, '${dados.galeria}', '${dados.bloco || ""}', '${dados.cela}')">${tv.toString().padStart(2, "0")}</span></div>
                <div class="item-row"><span>Rádio:</span> <span class="badge-custom bg-blue-light cursor-pointer" onclick="abrirModalItens('Radio', ${radio}, '${dados.galeria}', '${dados.bloco || ""}', '${dados.cela}')">${radio.toString().padStart(2, "0")}</span></div>
                <div class="item-row"><span>Chaleira:</span> <span class="badge-custom bg-blue-light cursor-pointer" onclick="abrirModalItens('Chaleira', ${chaleira}, '${dados.galeria}', '${dados.bloco || ""}', '${dados.cela}')">${chaleira.toString().padStart(2, "0")}</span></div>
                <div class="item-row"><span>Ventilador:</span> <span class="badge-custom bg-blue-light cursor-pointer" onclick="abrirModalItens('Ventilador', ${ventilador}, '${dados.galeria}', '${dados.bloco || ""}', '${dados.cela}')">${ventilador.toString().padStart(2, "0")}</span></div>
                <div class="item-row"><span>Chuveiro:</span> <span class="badge-custom bg-blue-light cursor-pointer" onclick="abrirModalItens('Chuveiro', ${chuveiro}, '${dados.galeria}', '${dados.bloco || ""}', '${dados.cela}')">${chuveiro.toString().padStart(2, "0")}</span></div>
                <div class="item-row"><span>Violão:</span> <span class="badge-custom bg-blue-light cursor-pointer" onclick="abrirModalItens('Bola', ${violao}, '${dados.galeria}', '${dados.bloco || ""}', '${dados.cela}')">${violao.toString().padStart(2, "0")}</span></div>
                <div class="item-row"><span>Banqueta:</span> <span class="badge-custom bg-blue-light cursor-pointer" onclick="abrirModalItens('Banqueta', ${banqueta}, '${dados.galeria}', '${dados.bloco || ""}', '${dados.cela}')">${banqueta.toString().padStart(2, "0")}</span></div>
                <div class="item-row"><span>Extensão:</span> <span class="badge-custom bg-blue-light cursor-pointer" onclick="abrirModalItens('Extensão', ${extensao}, '${dados.galeria}', '${dados.bloco || ""}', '${dados.cela}')">${extensao.toString().padStart(2, "0")}</span></div>
                <div class="item-row"><span>Máq. Cabelo:</span> <span class="badge-custom bg-blue-light cursor-pointer" onclick="abrirModalItens('MaquinaCabelo', ${maqCabelo}, '${dados.galeria}', '${dados.bloco || ""}', '${dados.cela}')">${maqCabelo.toString().padStart(2, "0")}</span></div>
                <div class="item-row"><span>Outros:</span> <span class="badge-custom bg-blue-light cursor-pointer" onclick="abrirModalItens('Outro', ${outros}, '${dados.galeria}', '${dados.bloco || ""}', '${dados.cela}')">${outros.toString().padStart(2, "0")}</span></div>

                <div class="mt-5">
                    <h5 class="text-center">Medida Disciplinar <span class="badge-custom bg-red-bright">${totalMDs.toString().padStart(2, "0")}</span></h5>
    `;

    // Medidas Disciplinares
    if (dados.medidas_disciplinares && dados.medidas_disciplinares.length > 0) {
      dados.medidas_disciplinares.forEach((md) => {
        const nomeInfo = getNomeExibicao(md);
        const nomeExibicao = formatarNomeCompleto(md);
        const dataInicio = new Date(md.data_inicio).toLocaleDateString("pt-BR");
        const dataFim = new Date(md.data_fim).toLocaleDateString("pt-BR");

        html += `
                <p class="text-red-custom small text-center">${md.id_interno} - ${nomeExibicao}<br>${dataInicio} -> ${dataFim}</p>
            `;
      });
    } else {
      html += `
            <p class="text-red-custom small text-center">Nenhuma MD ativa</p>
        `;
    }

    html += `
                </div>
            </div>

            <!-- Coluna Direita: Internos -->
            <div class="col-7 pl-4">
                <h5 class="mb-3 text-center">Internos <span class="badge-custom bg-blue-light">${totalInternos.toString().padStart(2, "0")}</span></h5>
                <ul class="list-unstyled">
    `;

    // Lista de internos
    if (dados.internos && dados.internos.length > 0) {
      dados.internos.forEach((interno) => {
        const nomeInfo = getNomeExibicao(interno);
        const nomeExibicao = formatarNomeCompleto(interno);

        // Verificar MD de forma mais robusta
        let temMD = false;
        if (
          dados.medidas_disciplinares &&
          dados.medidas_disciplinares.length > 0
        ) {
          temMD = dados.medidas_disciplinares.some((md) => {
            // Converter ambos para número para comparação
            return Number(md.id_interno) === Number(interno.ipen);
          });
        }

        // Verificar se tem movimento de entrada
        let subText = "";
        let liClass = "";

        if (interno.movimento && interno.movimento.antiga_cela_label) {
          const dataMov = new Date(
            interno.movimento.data_alteracao,
          ).toLocaleDateString("pt-BR");
          subText = `<span class="sub-text">=> Veio da ${interno.movimento.antiga_cela_label} em ${dataMov}</span>`;
          liClass = "text-yellow-custom";
        }

        // Se tem MD ativa
        if (temMD) {
          const md = dados.medidas_disciplinares.find(
            (md) => md.id_interno === interno.ipen,
          );
          if (md) {
            const dataFim = new Date(md.data_fim).toLocaleDateString("pt-BR");
            subText += `<br><span class="sub-text">=> Em MD até ${dataFim}</span>`;
            liClass = liClass
              ? liClass + " text-red-custom"
              : "text-red-custom";
          }
        }

        html += `
                <li class="${liClass}">${interno.ipen} - ${nomeExibicao}${subText}</li>
            `;
      });
    } else {
      html += `
            <li class="text-muted">Nenhum interno nesta cela</li>
        `;
    }

    html += `
                </ul>
            </div>
        </div>

        <!-- Rodapé: Histórico Limitado -->
        <div class="history-section mt-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <p class="text-center small mb-0">Histórico de Movimentações</p>
                <button class="btn btn-sm btn-outline-light" onclick="abrirModalHistorico('${dados.galeria}', '${dados.bloco || ""}', '${dados.cela}')">Ver mais</button>
            </div>
    `;

    // Combinar entradas, saídas e MDs em ordem cronológica
    const movimentacoes = [];

    // Adicionar saídas
    if (dados.saidas) {
      dados.saidas.forEach((saida) => {
        movimentacoes.push({
          tipo: "saida",
          ipen: saida.ipen,
          nome: saida.nome,
          data: saida.data_saida,
          destino: saida.destino,
          descricao: `Foi para ${saida.destino}`,
          class: "text-danger",
        });
      });
    }

    // Adicionar entradas
    if (dados.entradas) {
      dados.entradas.forEach((entrada) => {
        movimentacoes.push({
          tipo: "entrada",
          ipen: entrada.ipen,
          nome: entrada.nome,
          data: entrada.data_entrada,
          origem: entrada.origem,
          descricao: `Veio da ${entrada.origem}`,
          class: "text-success",
        });
      });
    }

    // Adicionar medidas disciplinares
    if (dados.medidas_disciplinares) {
      dados.medidas_disciplinares.forEach((md) => {
        movimentacoes.push({
          tipo: "md",
          ipen: md.id_interno,
          nome: md.nome_social || md.nome,
          data: md.data_inicio,
          descricao: `Recebeu MD`,
          class: "text-warning",
        });
      });
    }

    // Ordenar por data (mais recentes primeiro)
    movimentacoes.sort((a, b) => new Date(b.data) - new Date(a.data));

    // Limitar às 5 mais recentes
    movimentacoes.slice(0, 5).forEach((mov) => {
      const dataFormatada = new Date(mov.data).toLocaleDateString("pt-BR");
      html += `
            <div class="history-item ${mov.class}">${mov.ipen} - ${mov.nome} - ${mov.descricao} em ${dataFormatada}</div>
        `;
    });

    // Adicionar botão para carregar histórico completo se houver mais movimentações
    if (movimentacoes.length > 5) {
      html += `
        <div class="text-center mt-3">
            <button class="btn btn-sm btn-primary" onclick="carregarHistoricoCompleto('${dados.galeria}', '${dados.bloco || ""}', '${dados.cela}')">
                <i class="fas fa-history mr-1"></i> Carregar histórico completo
            </button>
        </div>
      `;
    }

    if (movimentacoes.length === 0) {
      html += `
            <div class="history-item text-muted">Nenhuma movimentação recente</div>
        `;
    }

    html += `
        </div>
    `;

    bodyContent.innerHTML = html;
  }

  // Função para carregar histórico completo
  window.carregarHistoricoCompleto = async (galeria, bloco, cela) => {
    // Fechar offcanvas atual
    window.fecharOffcanvasCela();

    // Abrir offcanvas com o novo interno
    window.abrirCela(galeria, bloco, cela, "", true);
  };

  // ==================== FUNÇÕES DOS MODAIS ====================
  window.abrirModalItens = async (
    tipoItem,
    quantidade,
    galeria,
    bloco,
    cela,
  ) => {
    if (quantidade === 0) {
      alert(`Não há ${tipoItem.toLowerCase()}(s) nesta cela.`);
      return;
    }

    // Mapeamento dinâmico de colunas por tipo de item
    const colunasPorTipo = {
      TV: [
        { key: "ipen", label: "IPEN" },
        { key: "nome", label: "Nome" },
        { key: "marca_modelo", label: "Marca/Modelo" },
        { key: "cor", label: "Cor" },
        { key: "polegadas", label: "Polegadas" },
        { key: "tem_fonte", label: "Fonte" },
        { key: "tem_controle", label: "Controle" },
        { key: "data_entrada", label: "Data Entrada" },
        { key: "observacoes", label: "Observações" },
      ],
      Ventilador: [
        { key: "ipen", label: "IPEN" },
        { key: "nome", label: "Nome" },
        { key: "marca_modelo", label: "Marca/Modelo" },
        { key: "cor", label: "Cor" },
        { key: "tamanho", label: "Tamanho" },
        { key: "data_entrada", label: "Data Entrada" },
        { key: "observacoes", label: "Observações" },
      ],
      Radio: [
        { key: "ipen", label: "IPEN" },
        { key: "nome", label: "Nome" },
        { key: "marca_modelo", label: "Marca/Modelo" },
        { key: "cor", label: "Cor" },
        { key: "tem_fonte", label: "Fonte" },
        { key: "data_entrada", label: "Data Entrada" },
        { key: "observacoes", label: "Observações" },
      ],
      Chaleira: [
        { key: "ipen", label: "IPEN" },
        { key: "nome", label: "Nome" },
        { key: "marca_modelo", label: "Marca/Modelo" },
        { key: "cor", label: "Cor" },
        { key: "capacidade", label: "Capacidade" },
        { key: "data_entrada", label: "Data Entrada" },
        { key: "observacoes", label: "Observações" },
      ],
      Chuveiro: [
        { key: "ipen", label: "IPEN" },
        { key: "nome", label: "Nome" },
        { key: "marca_modelo", label: "Marca/Modelo" },
        { key: "cor", label: "Cor" },
        { key: "data_entrada", label: "Data Entrada" },
        { key: "observacoes", label: "Observações" },
      ],
      MaquinaCabelo: [
        { key: "ipen", label: "IPEN" },
        { key: "nome", label: "Nome" },
        { key: "marca_modelo", label: "Marca/Modelo" },
        { key: "cor", label: "Cor" },
        { key: "tem_fonte", label: "Fonte" },
        { key: "data_entrada", label: "Data Entrada" },
        { key: "observacoes", label: "Observações" },
      ],
      Extensao: [
        { key: "ipen", label: "IPEN" },
        { key: "nome", label: "Nome" },
        { key: "marca_modelo", label: "Marca/Modelo" },
        { key: "cor", label: "Cor" },
        { key: "comprimento", label: "Comprimento" },
        { key: "data_entrada", label: "Data Entrada" },
        { key: "observacoes", label: "Observações" },
      ],
      Bola: [
        { key: "ipen", label: "IPEN" },
        { key: "nome", label: "Nome" },
        { key: "marca_modelo", label: "Marca/Modelo" },
        { key: "cor", label: "Cor" },
        { key: "data_entrada", label: "Data Entrada" },
        { key: "observacoes", label: "Observações" },
      ],
      Banqueta: [
        { key: "ipen", label: "IPEN" },
        { key: "nome", label: "Nome" },
        { key: "marca_modelo", label: "Marca/Modelo" },
        { key: "cor", label: "Cor" },
        { key: "data_entrada", label: "Data Entrada" },
        { key: "observacoes", label: "Observações" },
      ],
      Violao: [
        { key: "ipen", label: "IPEN" },
        { key: "nome", label: "Nome" },
        { key: "marca_modelo", label: "Marca/Modelo" },
        { key: "cor", label: "Cor" },
        { key: "data_entrada", label: "Data Entrada" },
        { key: "observacoes", label: "Observações" },
      ],
      Outro: [
        { key: "ipen", label: "IPEN" },
        { key: "nome", label: "Nome" },
        { key: "marca_modelo", label: "Marca/Modelo" },
        { key: "cor", label: "Cor" },
        { key: "data_entrada", label: "Data Entrada" },
        { key: "observacoes", label: "Observações" },
      ],
    };

    // Obter colunas para o tipo atual, ou usar padrão
    const colunas = colunasPorTipo[tipoItem] || colunasPorTipo["Outro"];

    const modal = document.getElementById("modalItens");
    const modalBody = document.getElementById("modalItensBody");

    // Verificar se modal existe
    if (!modal || !modalBody) {
      console.error("Modal não encontrado na página");
      alert(
        "Erro: Modal não disponível. Recarregue a página e tente novamente.",
      );
      return;
    }

    // Mostrar modal com Bootstrap
    $("#modalItens").modal("show");

    // Mostrar loading
    modalBody.innerHTML = `
        <div style="text-align: center; padding: 30px;">
            <div class="loading-spinner"></div>
            <p>Carregando ${tipoItem.toLowerCase()}(s)...</p>
        </div>
    `;

    try {
      const fd = new FormData();
      fd.append("action", "fetch_itens_cela");
      fd.append("galeria", galeria);
      fd.append("bloco", bloco);
      fd.append("cela", cela);
      fd.append("tipo_item", tipoItem);

      const res = await fetch(
        "modulos/geral/painel_internos/internos_painel_controller.php",
        {
          method: "POST",
          body: fd,
        },
      );

      // Verificar se a resposta é válida
      const responseText = await res.text();
      if (!responseText || responseText.trim() === "") {
        throw new Error("Resposta vazia do servidor");
      }

      let json;
      try {
        json = JSON.parse(responseText);
      } catch (e) {
        console.error("Resposta do servidor:", responseText);
        throw new Error("Resposta JSON inválida: " + e.message);
      }

      if (json.success) {
        let html = `
                <h6 class="mb-3">${tipoItem}(s) - Cela ${bloco ? `${bloco}-${cela}` : `${galeria}-${cela}`}</h6>
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
            `;

        // Gerar cabeçalho dinâmico
        colunas.forEach((coluna) => {
          html += `<th>${coluna.label}</th>`;
        });

        html += `
                            </tr>
                        </thead>
                        <tbody>
            `;

        if (json.itens && json.itens.length > 0) {
          json.itens.forEach((item) => {
            html += `<tr>`;

            // Gerar células dinâmicas
            colunas.forEach((coluna) => {
              let valor = item[coluna.key] || "-";

              // Tratamento especial para cada tipo de campo
              if (coluna.key === "tem_fonte" || coluna.key === "tem_controle") {
                valor =
                  valor === "Sim" ? "✅ Sim" : valor === "Não" ? "❌ Não" : "-";
              } else if (coluna.key === "data_entrada") {
                valor = valor
                  ? new Date(valor).toLocaleDateString("pt-BR")
                  : "-";
              } else if (coluna.key === "marca_modelo") {
                // Adicionar informações complementares para alguns tipos
                let descricao = valor;
                if (tipoItem === "TV" && item.polegadas) {
                  descricao += ` (${item.polegadas}")`;
                } else if (tipoItem === "Ventilador" && item.tamanho) {
                  descricao += ` - ${item.tamanho}`;
                } else if (tipoItem === "Chaleira" && item.capacidade) {
                  descricao += ` - ${item.capacidade}`;
                } else if (tipoItem === "Extensao" && item.comprimento) {
                  descricao += ` - ${item.comprimento}`;
                }
                valor = descricao;
              }

              html += `<td>${valor}</td>`;
            });

            html += `</tr>`;
          });
        } else {
          const colspan = colunas.length;
          html += `
                    <tr>
                        <td colspan="${colspan}" class="text-center text-muted">
                            Nenhum ${tipoItem.toLowerCase()} encontrado
                        </td>
                    </tr>
                `;
        }

        html += `
                        </tbody>
                    </table>
                </div>
            `;

        modalBody.innerHTML = html;
      } else {
        throw new Error(json.error || "Erro ao carregar itens");
      }
    } catch (e) {
      console.error("Erro ao carregar itens:", e);
      modalBody.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Erro ao carregar ${tipoItem.toLowerCase()}(s).
            </div>
        `;
    }
  };

  window.abrirModalHistorico = async (galeria, bloco, cela) => {
    const modal = document.getElementById("historicoModal");
    const modalBody = document.getElementById("historicoModalBody");

    // Verificar se modal existe
    if (!modal || !modalBody) {
      console.error("Modal histórico não encontrado na página");
      alert(
        "Erro: Modal histórico não disponível. Recarregue a página e tente novamente.",
      );
      return;
    }

    // Mostrar modal
    modal.style.display = "block";
    modal.style.zIndex = "1060";

    // Mostrar loading
    modalBody.innerHTML = `
        <div style="text-align: center; padding: 30px;">
            <div class="loading-spinner"></div>
            <p>Carregando histórico completo...</p>
        </div>
    `;

    try {
      const fd = new FormData();
      fd.append("action", "fetch_historico_cela");
      fd.append("galeria", galeria);
      fd.append("bloco", bloco);
      fd.append("cela", cela);

      const res = await fetch(
        "modulos/geral/painel_internos/internos_painel_controller.php",
        {
          method: "POST",
          body: fd,
        },
      );

      // Verificar se a resposta é válida
      const responseText = await res.text();
      if (!responseText || responseText.trim() === "") {
        throw new Error("Resposta vazia do servidor");
      }

      let json;
      try {
        json = JSON.parse(responseText);
      } catch (e) {
        console.error("Resposta do servidor:", responseText);
        throw new Error("Resposta JSON inválida: " + e.message);
      }

      if (json.success) {
        let html = `
                <h6 class="mb-3">Histórico Completo - Cela ${bloco ? `${bloco}-${cela}` : `${galeria}-${cela}`}</h6>
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>IPEN</th>
                                <th>Nome</th>
                                <th>Origem/Destino</th>
                                <th>Observações</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

        if (json.historico && json.historico.length > 0) {
          json.historico.forEach((item) => {
            const nome = item.nome_social || item.nome;
            const dataFormatada = new Date(item.data).toLocaleDateString(
              "pt-BR",
            );

            // Tratamento específico para tipo de histórico
            let tipoClass, tipoText, origemDestino;

            if (item.tipo === "medida_disciplinar") {
              tipoClass = "text-warning";
              tipoText = "MD";
              origemDestino = item.destino || "Medida Disciplinar";
            } else if (item.tipo === "entrada") {
              tipoClass = "text-success";
              tipoText = "ENTRADA";
              origemDestino = item.origem || "Origem desconhecida";
            } else if (item.tipo === "saida") {
              tipoClass = "text-danger";
              tipoText = "SAÍDA";
              origemDestino = item.destino || "Destino desconhecido";
            } else {
              tipoClass = "text-info";
              tipoText = item.tipo?.toUpperCase() || "OUTRO";
              origemDestino = item.origem || item.destino || "N/A";
            }

            html += `
                        <tr>
                            <td>${dataFormatada}</td>
                            <td class="${tipoClass}">${tipoText}</td>
                            <td>${item.ipen}</td>
                            <td>${nome}</td>
                            <td>${origemDestino}</td>
                            <td>${item.observacoes || "-"}</td>
                        </tr>
                    `;
          });
        } else {
          html += `
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            Nenhuma movimentação encontrada
                        </td>
                    </tr>
                `;
        }

        html += `
                        </tbody>
                    </table>
                </div>
            `;

        modalBody.innerHTML = html;
      } else {
        throw new Error(json.error || "Erro ao carregar histórico");
      }
    } catch (e) {
      console.error("Erro ao carregar histórico:", e);
      modalBody.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Erro ao carregar histórico completo.
            </div>
        `;
    }
  };

  window.fecharModal = (modalId) => {
    document.getElementById(modalId).style.display = "none";
  };

  // ==================== INICIALIZAÇÃO ====================
  document.addEventListener("DOMContentLoaded", function () {
    console.log("Internos Painel: Sistema inicializado");

    // Verificar se elementos essenciais existem
    if (!inputBusca || !resultadosBusca) {
      console.error("Elementos essenciais não encontrados");
      return;
    }

    // Configurar eventos adicionais
    setupEventosAdicionais();
  });

  function setupEventosAdicionais() {
    // Fechar resultados ao clicar fora
    document.addEventListener("click", (e) => {
      if (!e.target.closest(".busca-resultados")) {
        resultadosBusca.style.display = "none";
      }
    });

    // Fechar offcanvas ao pressionar Escape
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        window.fecharOffcanvasCela();
      }
    });
  }

  // As funções já são expostas via window durante sua definição e não dependem
  // de variáveis locais com os mesmos nomes neste escopo. Mantemos aqui para
  // documentação de comportamento global.

  // Função para renderizar histórico de movimentações unificado
  function renderHistoricoMovimentacoes(json) {
    const movimentacoes = json.movimentacoes || [];

    if (movimentacoes.length === 0) {
      return `<div class="history-section mt-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <p class="text-center small mb-0">Histórico de Movimentações</p>
          <button class="btn btn-sm btn-outline-light" onclick="abrirModalHistorico('${json.galeria}', '${json.bloco || ""}', '${json.cela}')">Ver mais</button>
        </div>
        <div class="history-item text-muted">Nenhuma movimentação encontrada</div>
      </div>`;
    }

    let html = `
      <div class="history-section mt-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <p class="text-center small mb-0">Histórico de Movimentações</p>
          <button class="btn btn-sm btn-outline-light" onclick="abrirModalHistorico('${json.galeria}', '${json.bloco || ""}', '${json.cela}')">Ver mais</button>
        </div>
        <div class="timeline">
    `;

    movimentacoes.forEach((mov) => {
      const data = new Date(mov.data_alteracao);
      const dataFormatada =
        data.toLocaleDateString("pt-BR") +
        " " +
        data.toLocaleTimeString("pt-BR", {
          hour: "2-digit",
          minute: "2-digit",
        });

      // Definir ícone e cor baseado no tipo
      let icon = "fas fa-exchange-alt";
      let colorClass = "text-info";

      if (mov.tipo_movimentacao.includes("ENTROU")) {
        icon = "fas fa-sign-in-alt";
        colorClass = "text-success";
      } else if (mov.tipo_movimentacao.includes("SAIU")) {
        icon = "fas fa-sign-out-alt";
        colorClass = "text-danger";
      } else if (mov.tipo_movimentacao.includes("INATIVADO")) {
        icon = "fas fa-user-times";
        colorClass = "text-secondary";
      }

      html += `
        <div class="timeline-item mb-3">
          <div class="d-flex">
            <div class="me-3">
              <i class="${icon} ${colorClass}"></i>
            </div>
            <div class="flex-grow-1">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <strong class="${colorClass}">${mov.ipen} - ${mov.nome || ""}</strong>
                  <div class="small text-muted">${mov.tipo_movimentacao}</div>
                  ${mov.origem_destino_completo ? `<div class="small text-info">${mov.origem_destino_completo}</div>` : ""}
                </div>
                <div class="text-right">
                  <small class="text-muted">${dataFormatada}</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
    });

    html += `
        </div>
      </div>
    `;

    return html;
  }

  // Fechar proteção global do carregamento apenas uma vez
}
