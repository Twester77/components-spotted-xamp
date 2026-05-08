#include <stdio.h>
#include <stdlib.h>
#include <time.h>
#include <string.h>
#include <locale.h>   // Para acentos brasileiros

int main() {
    setlocale(LC_ALL, "portuguese");  // Habilita acentos

    srand(time(NULL));

    // ==================================================
    // 1. NOMES DOS JOGADORES (aceitando espaços)
    // ==================================================
    char nome1[50], nome2[50];
    printf("Digite o nome do Jogador 1: ");
    fgets(nome1, 50, stdin);
    nome1[strcspn(nome1, "\n")] = 0;  // remove o '\n' do final

    printf("Digite o nome do Jogador 2: ");
    fgets(nome2, 50, stdin);
    nome2[strcspn(nome2, "\n")] = 0;

    // ==================================================
    // 2. VARIÁVEIS PRINCIPAIS
    // ==================================================
    int baralho[20];
    int mao1[20], mao2[20];
    int qtd1 = 0, qtd2 = 0;
    int penalidade1 = 0, penalidade2 = 0;

    int indice_baralho = 0;
    int rodada = 1;
    int fim_jogo = 0;
    int vez = 0;              // 0 = jogador 1, 1 = jogador 2
    int manilha;              // 0=J, 1=Q, 2=K

    // ==================================================
    // 3. APRESENTAÇÃO E REGRAS
    // ==================================================
    printf("\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");
    printf("====================================================\n");
    printf("          HOUSE OF CARDS - PIFE COM BLEFE\n");
    printf("====================================================\n\n");
    printf("REGRAS RÁPIDAS:\n");
    printf("- Baralho: 6 VALETES (J), 6 DAMAS (Q), 6 REIS (K), 2 CORINGAS.\n");
    printf("- Cada rodada sorteia uma MANILHA (J, Q ou K).\n");
    printf("- CORINGA SEMPRE vale como manilha.\n");
    printf("- Na sua vez, descarte 1 ou 2 cartas e DECLARE se são manilha.\n");
    printf("- O oponente pode ACREDITAR ou QUESTIONAR.\n");
    printf("- Se questionar CERTO: quem mentiu COMPRA penalidade.\n");
    printf("- Se questionar ERRADO: quem questionou COMPRA penalidade.\n");
    printf("- Penalidade acumula: 1ª vez compra 2, 2ª compra 4, 3ª compra 6...\n");
    printf("- Quem zerar a mão primeiro VENCE.\n\n");
    printf("Pressione ENTER para começar...");
    getchar();

    // ==================================================
    // 4. LOOP PRINCIPAL (VÁRIAS RODADAS)
    // ==================================================
    while (!fim_jogo) {
        // ----- 4.1 INICIALIZA A RODADA -----
        printf("\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");
        printf("====================================================\n");
        printf("                    RODADA %d\n", rodada);
        printf("====================================================\n");

        int idx = 0;
        for (int i = 0; i < 6; i++) baralho[idx++] = 0;
        for (int i = 0; i < 6; i++) baralho[idx++] = 1;
        for (int i = 0; i < 6; i++) baralho[idx++] = 2;
        for (int i = 0; i < 2; i++) baralho[idx++] = 3;

        for (int i = 0; i < 20; i++) {
            int j = rand() % 20;
            int temp = baralho[i];
            baralho[i] = baralho[j];
            baralho[j] = temp;
        }

        indice_baralho = 0;
        qtd1 = 0;
        qtd2 = 0;

        manilha = rand() % 3;

        for (int i = 0; i < 5; i++) {
            mao1[qtd1++] = baralho[indice_baralho++];
            mao2[qtd2++] = baralho[indice_baralho++];
        }

        printf("\nMANILHA DA RODADA: ");
        if (manilha == 0) printf("VALETE (J)\n");
        else if (manilha == 1) printf("DAMA (Q)\n");
        else printf("REI (K)\n");
        printf("(Coringa sempre conta como manilha)\n");

        printf("\nSTATUS INICIAL:\n");
        printf("%s: %d cartas (penalidade: %d)\n", nome1, qtd1, penalidade1);
        printf("%s: %d cartas (penalidade: %d)\n", nome2, qtd2, penalidade2);
        printf("\nPressione ENTER para começar a rodada...");
        getchar();

        vez = 0;

        while (!fim_jogo) {
            if (qtd1 == 0) {
                printf("\n====================================================\n");
                printf(">>> %s VENCEU! (zerou a mão) <<<\n", nome1);
                fim_jogo = 1;
                break;
            }
            if (qtd2 == 0) {
                printf("\n====================================================\n");
                printf(">>> %s VENCEU! (zerou a mão) <<<\n", nome2);
                fim_jogo = 1;
                break;
            }

            // ---------- TURNO DO JOGADOR 1 ----------
            if (vez == 0) {
                printf("\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");
                printf("====================================================\n");
                printf("                VEZ DE %s\n", nome1);
                printf("====================================================\n");

                printf("\nSUAS CARTAS:\n");
                printf("----------------------------------------\n");
                for (int i = 0; i < qtd1; i++) {
                    printf("[%d] ", i);
                    if (mao1[i] == 0) printf("VALETE (J)\n");
                    else if (mao1[i] == 1) printf("DAMA (Q)\n");
                    else if (mao1[i] == 2) printf("REI (K)\n");
                    else printf("CORINGA\n");
                }
                printf("----------------------------------------\n");
                printf("Total: %d cartas\n", qtd1);
                printf("Manilha atual: ");
                if (manilha == 0) printf("VALETE (J)\n");
                else if (manilha == 1) printf("DAMA (Q)\n");
                else printf("REI (K)\n");
                printf("Penalidade acumulada: %d (se pego, compra %d cartas)\n",
                       penalidade1, penalidade1 * 2);

                int qtd_descarte;
                printf("\nQuantas cartas vai descartar (1 ou 2)? ");
                scanf("%d", &qtd_descarte);
                while (getchar() != '\n');

                if (qtd_descarte < 1 || qtd_descarte > 2) {
                    printf("Opção inválida! Será considerado 1 carta.\n");
                    qtd_descarte = 1;
                }

                int indices[2], cartas_descartadas[2];
                int descarte_valido = 1;
                for (int d = 0; d < qtd_descarte; d++) {
                    printf("Escolha o índice da %dª carta (0 a %d): ", d+1, qtd1-1);
                    scanf("%d", &indices[d]);
                    while (getchar() != '\n');
                    if (indices[d] < 0 || indices[d] >= qtd1) {
                        printf("Índice inválido! Você perde a vez.\n");
                        descarte_valido = 0;
                        break;
                    }
                    cartas_descartadas[d] = mao1[indices[d]];
                }

                if (!descarte_valido) {
                    vez = 1;
                    printf("\nPressione ENTER...");
                    getchar();
                    continue;
                }

                for (int d = qtd_descarte-1; d >= 0; d--) {
                    for (int i = indices[d]; i < qtd1-1; i++) {
                        mao1[i] = mao1[i+1];
                    }
                    qtd1--;
                }

                printf("\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");
                printf("====================================================\n");
                printf("          DECLARAÇÃO DE %s\n", nome1);
                printf("====================================================\n");
                printf("%s descartou ", nome1);
                if (qtd_descarte == 1) printf("1 carta.\n");
                else printf("2 cartas.\n");

                int declaracao;
                printf("\nDeclare se a(s) carta(s) descartada(s) É ou NÃO a manilha:\n");
                printf("  1 - \"É a manilha\"\n");
                printf("  2 - \"NÃO é a manilha\"\n");
                printf("Escolha: ");
                scanf("%d", &declaracao);
                while (getchar() != '\n');

                char *texto_decl = (declaracao == 1) ? "É a manilha" : "NÃO é a manilha";
                printf("\n====================================================\n");
                printf("%s DECLARA: \"", nome1);
                if (qtd_descarte == 1) printf("Esta carta %s\"\n", texto_decl);
                else printf("Estas duas cartas %s\"\n", texto_decl);
                printf("====================================================\n");

                printf("\n%s, o que deseja fazer?\n", nome2);
                printf("  1 - ACREDITAR (concordar)\n");
                printf("  2 - QUESTIONAR (duvidar)\n");
                printf("Opção: ");
                int acao;
                scanf("%d", &acao);
                while (getchar() != '\n');

                if (acao == 2) {
                    printf("\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");
                    printf("====================================================\n");
                    printf("              REVELANDO AS CARTAS\n");
                    printf("====================================================\n");
                    int todas_verdadeiras = 1;
                    for (int d = 0; d < qtd_descarte; d++) {
                        int eh_manilha = (cartas_descartadas[d] == 3) || (cartas_descartadas[d] == manilha);
                        printf("\nCarta %d: ", d+1);
                        if (cartas_descartadas[d] == 0) printf("VALETE (J)\n");
                        else if (cartas_descartadas[d] == 1) printf("DAMA (Q)\n");
                        else if (cartas_descartadas[d] == 2) printf("REI (K)\n");
                        else printf("CORINGA\n");
                        printf("Status: %s\n", eh_manilha ? "É a manilha" : "NÃO é a manilha");
                        if (declaracao == 1) { if (!eh_manilha) todas_verdadeiras = 0; }
                        else { if (eh_manilha) todas_verdadeiras = 0; }
                    }

                    printf("\n====================================================\n");
                    if (todas_verdadeiras) {
                        printf("DECLARAÇÃO VERDADEIRA!\n");
                        printf("%s QUESTIONOU e ERROU!\n", nome2);
                        printf("Penalidade para %s\n", nome2);
                        penalidade2++;
                        int comprar = penalidade2 * 2;
                        printf("%s deve comprar %d cartas\n", nome2, comprar);
                        for (int i = 0; i < comprar; i++) {
                            if (indice_baralho < 20) {
                                mao2[qtd2++] = baralho[indice_baralho++];
                            } else break;
                        }
                    } else {
                        printf("DECLARAÇÃO FALSA! BLEFE DESCOBERTO!\n");
                        printf("%s foi pego no blefe!\n", nome1);
                        printf("Penalidade para %s\n", nome1);
                        penalidade1++;
                        int comprar = penalidade1 * 2;
                        printf("%s deve comprar %d cartas\n", nome1, comprar);
                        for (int i = 0; i < comprar; i++) {
                            if (indice_baralho < 20) {
                                mao1[qtd1++] = baralho[indice_baralho++];
                            } else break;
                        }
                    }
                    printf("\nNOVO STATUS:\n");
                    printf("%s: %d cartas (penalidade %d)\n", nome1, qtd1, penalidade1);
                    printf("%s: %d cartas (penalidade %d)\n", nome2, qtd2, penalidade2);
                } else {
                    printf("\n====================================================\n");
                    printf("%s ACREDITOU na declaração.\n", nome2);
                    printf("Jogo continua.\n");
                    printf("%s: %d cartas | %s: %d cartas\n", nome1, qtd1, nome2, qtd2);
                }

                printf("\nPressione ENTER...");
                getchar();

                if (qtd1 == 0) {
                    printf("\n====================================================\n");
                    printf(">>> %s VENCEU! (zerou a mão) <<<\n", nome1);
                    fim_jogo = 1;
                    break;
                }
                if (qtd2 == 0) {
                    printf("\n====================================================\n");
                    printf(">>> %s VENCEU! (zerou a mão) <<<\n", nome2);
                    fim_jogo = 1;
                    break;
                }
                if (indice_baralho >= 20) {
                    printf("\n====================================================\n");
                    printf("               BARALHO ESGOTADO!\n");
                    printf("====================================================\n");
                    if (qtd1 < qtd2) printf("%s vence (menos cartas)!\n", nome1);
                    else if (qtd2 < qtd1) printf("%s vence (menos cartas)!\n", nome2);
                    else printf("EMPATE!\n");
                    fim_jogo = 1;
                    break;
                }
                vez = 1;
            }
            // ---------- TURNO DO JOGADOR 2 (ESTRUTURA ANÁLOGA) ----------
            else {
                printf("\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");
                printf("====================================================\n");
                printf("                VEZ DE %s\n", nome2);
                printf("====================================================\n");

                printf("\nSUAS CARTAS:\n");
                printf("----------------------------------------\n");
                for (int i = 0; i < qtd2; i++) {
                    printf("[%d] ", i);
                    if (mao2[i] == 0) printf("VALETE (J)\n");
                    else if (mao2[i] == 1) printf("DAMA (Q)\n");
                    else if (mao2[i] == 2) printf("REI (K)\n");
                    else printf("CORINGA\n");
                }
                printf("----------------------------------------\n");
                printf("Total: %d cartas\n", qtd2);
                printf("Manilha atual: ");
                if (manilha == 0) printf("VALETE (J)\n");
                else if (manilha == 1) printf("DAMA (Q)\n");
                else printf("REI (K)\n");
                printf("Penalidade acumulada: %d (se pego, compra %d cartas)\n",
                       penalidade2, penalidade2 * 2);

                int qtd_descarte;
                printf("\nQuantas cartas vai descartar (1 ou 2)? ");
                scanf("%d", &qtd_descarte);
                while (getchar() != '\n');

                if (qtd_descarte < 1 || qtd_descarte > 2) {
                    printf("Opção inválida! Será considerado 1 carta.\n");
                    qtd_descarte = 1;
                }

                int indices[2], cartas_descartadas[2];
                int descarte_valido = 1;
                for (int d = 0; d < qtd_descarte; d++) {
                    printf("Escolha o índice da %dª carta (0 a %d): ", d+1, qtd2-1);
                    scanf("%d", &indices[d]);
                    while (getchar() != '\n');
                    if (indices[d] < 0 || indices[d] >= qtd2) {
                        printf("Índice inválido! Você perde a vez.\n");
                        descarte_valido = 0;
                        break;
                    }
                    cartas_descartadas[d] = mao2[indices[d]];
                }

                if (!descarte_valido) {
                    vez = 0;
                    printf("\nPressione ENTER...");
                    getchar();
                    continue;
                }

                for (int d = qtd_descarte-1; d >= 0; d--) {
                    for (int i = indices[d]; i < qtd2-1; i++) {
                        mao2[i] = mao2[i+1];
                    }
                    qtd2--;
                }

                printf("\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");
                printf("====================================================\n");
                printf("          DECLARAÇÃO DE %s\n", nome2);
                printf("====================================================\n");
                printf("%s descartou ", nome2);
                if (qtd_descarte == 1) printf("1 carta.\n");
                else printf("2 cartas.\n");

                int declaracao;
                printf("\nDeclare se a(s) carta(s) descartada(s) É ou NÃO a manilha:\n");
                printf("  1 - \"É a manilha\"\n");
                printf("  2 - \"NÃO é a manilha\"\n");
                printf("Escolha: ");
                scanf("%d", &declaracao);
                while (getchar() != '\n');

                char *texto_decl = (declaracao == 1) ? "É a manilha" : "NÃO é a manilha";
                printf("\n====================================================\n");
                printf("%s DECLARA: \"", nome2);
                if (qtd_descarte == 1) printf("Esta carta %s\"\n", texto_decl);
                else printf("Estas duas cartas %s\"\n", texto_decl);
                printf("====================================================\n");

                printf("\n%s, o que deseja fazer?\n", nome1);
                printf("  1 - ACREDITAR (concordar)\n");
                printf("  2 - QUESTIONAR (duvidar)\n");
                printf("Opção: ");
                int acao;
                scanf("%d", &acao);
                while (getchar() != '\n');

                if (acao == 2) {
                    printf("\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");
                    printf("====================================================\n");
                    printf("              REVELANDO AS CARTAS\n");
                    printf("====================================================\n");
                    int todas_verdadeiras = 1;
                    for (int d = 0; d < qtd_descarte; d++) {
                        int eh_manilha = (cartas_descartadas[d] == 3) || (cartas_descartadas[d] == manilha);
                        printf("\nCarta %d: ", d+1);
                        if (cartas_descartadas[d] == 0) printf("VALETE (J)\n");
                        else if (cartas_descartadas[d] == 1) printf("DAMA (Q)\n");
                        else if (cartas_descartadas[d] == 2) printf("REI (K)\n");
                        else printf("CORINGA\n");
                        printf("Status: %s\n", eh_manilha ? "É a manilha" : "NÃO é a manilha");
                        if (declaracao == 1) { if (!eh_manilha) todas_verdadeiras = 0; }
                        else { if (eh_manilha) todas_verdadeiras = 0; }
                    }

                    printf("\n====================================================\n");
                    if (todas_verdadeiras) {
                        printf("DECLARAÇÃO VERDADEIRA!\n");
                        printf("%s QUESTIONOU e ERROU!\n", nome1);
                        printf("Penalidade para %s\n", nome1);
                        penalidade1++;
                        int comprar = penalidade1 * 2;
                        printf("%s deve comprar %d cartas\n", nome1, comprar);
                        for (int i = 0; i < comprar; i++) {
                            if (indice_baralho < 20) {
                                mao1[qtd1++] = baralho[indice_baralho++];
                            } else break;
                        }
                    } else {
                        printf("DECLARAÇÃO FALSA! BLEFE DESCOBERTO!\n");
                        printf("%s foi pego no blefe!\n", nome2);
                        printf("Penalidade para %s\n", nome2);
                        penalidade2++;
                        int comprar = penalidade2 * 2;
                        printf("%s deve comprar %d cartas\n", nome2, comprar);
                        for (int i = 0; i < comprar; i++) {
                            if (indice_baralho < 20) {
                                mao2[qtd2++] = baralho[indice_baralho++];
                            } else break;
                        }
                    }
                    printf("\nNOVO STATUS:\n");
                    printf("%s: %d cartas (penalidade %d)\n", nome1, qtd1, penalidade1);
                    printf("%s: %d cartas (penalidade %d)\n", nome2, qtd2, penalidade2);
                } else {
                    printf("\n====================================================\n");
                    printf("%s ACREDITOU na declaração.\n", nome1);
                    printf("Jogo continua.\n");
                    printf("%s: %d cartas | %s: %d cartas\n", nome1, qtd1, nome2, qtd2);
                }

                printf("\nPressione ENTER...");
                getchar();

                if (qtd1 == 0) {
                    printf("\n====================================================\n");
                    printf(">>> %s VENCEU! (zerou a mão) <<<\n", nome1);
                    fim_jogo = 1;
                    break;
                }
                if (qtd2 == 0) {
                    printf("\n====================================================\n");
                    printf(">>> %s VENCEU! (zerou a mão) <<<\n", nome2);
                    fim_jogo = 1;
                    break;
                }
                if (indice_baralho >= 20) {
                    printf("\n====================================================\n");
                    printf("               BARALHO ESGOTADO!\n");
                    printf("====================================================\n");
                    if (qtd1 < qtd2) printf("%s vence (menos cartas)!\n", nome1);
                    else if (qtd2 < qtd1) printf("%s vence (menos cartas)!\n", nome2);
                    else printf("EMPATE!\n");
                    fim_jogo = 1;
                    break;
                }
                vez = 0;
            }
        }
        rodada++;
    }

    printf("\n====================================================\n");
    printf("                     FIM!\n");
    printf("====================================================\n");
    printf("Obrigado por jogar House of Cards!\n");
    return 0;
}