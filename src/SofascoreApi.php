<?php
/**
 * SofascoreApi foi desativado no projeto w99score (VPS).
 * Nenhuma requisição para a Sofascore é permitida neste projeto.
 * Todas as consultas à Sofascore foram transferidas para o coletor local (w99score-collector).
 */
class SofascoreApi {
    public function __construct() {
        throw new RuntimeException("Requisições para Sofascore estão desativadas neste ambiente. Utilize o coletor local.");
    }
}
