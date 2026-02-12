<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'check_session.php';
require_once 'Requisicao/config_oracle.php';

$response = ['success' => false, 'message' => '', 'data' => []];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Metodo HTTP nao suportado');
    }

    $oracle = new OracleConnection();
    if (!$oracle->connect()) {
        throw new Exception('Falha ao conectar com Oracle: ' . $oracle->getError());
    }

    $sql = <<<'SQL'
SELECT * FROM
        (
         SELECT * FROM
        (--BASE 1 DE DADOS (1 - Base console de ordens - atualizacao.xlsx)
        select
        ped.dt_entrega,
        ped.cliente,
        ped.representante,
        ped.num_pedido,
        ord.ordem_zero,
        ord.num_ordem,
        ord.estrutura,
        ord.nivel
        from
        (select
        connect_by_root ord1.num_ordem ordem_zero,
        ord2.num_ordem num_ordem,
        connect_by_root ord1.num_ordem||Sys_connect_by_path (ord2.num_ordem,',')||',' estrutura,
        level nivel
            from
        (select
        a.itpl_id,
        a.tmasc_item_id,
        substr (a.referencia,5,10) ordem_pai,
        a.dt_fim
            from
         focco3i.tperfil_itens a
            where
         tipo_ord_dem like 'DD%'
        and a.plano_id = 16)
        dd
        inner join (select
        itpl_id,
        tmasc_item_id,
        num_ordem,
        dt_ajustada
            FROM
        (SELECT
             itpl_id,
            tmasc_item_id,
            num_ordem,
                  (SELECT MIN(data)
             FROM (
                 SELECT c.data
                 FROM focco3i.tcalendarios c
                 WHERE c.util_indus = 1
                   AND c.data >= dt_fim_mrp
                 ORDER BY c.data
                 OFFSET tempo_seg ROWS FETCH NEXT 1 ROWS ONLY
             )
            ) AS
             dt_ajustada
            FROM
             (select
        a.itpl_id,
        a.tmasc_item_id,
        a.num_ordem,
        perf.dt_fim_mrp,
        nvl((case when a.tmasc_item_id is null then b.tempo_seg else bc.tempo_seg end),0) tempo_seg
            FROM
        focco3i.tordens a
        inner join (select
        to_number(referencia) num_ordem,
        (case
        when mensagem like '%Cance%' then dt_fim          when mensagem is not null then to_date(to_char(reverse(substr(reverse(mensagem),2,10))),'dd/mm/yyyy')
        else dt_fim end)
        dt_fim_mrp,
        mensagem
        from
        focco3i.tperfil_itens
        where
        tipo_ord_dem in ('OFA','OFF','OFM')
        and plano_id = 16)
        perf on (perf.num_ordem = a.num_ordem)
        inner join focco3i.titens_planejamento b on (a.itpl_id = b.id)
        left join focco3i.titens_plan_conf bc on (bc.itpl_id = b.id and bc.tmasc_item_id = a.tmasc_item_id)
            WHERE
        a.final = 0
        union all
        select
        a.itpl_id,
        a.tmasc_item_id,
        to_number(a.referencia) num_ordem,
        a.dt_fim dt_fim_mrp,
        nvl((case when a.tmasc_item_id is null then b.tempo_seg else bc.tempo_seg end),0) tempo_seg
        from
        focco3i.tperfil_itens a
        inner join focco3i.titens_planejamento b on (a.itpl_id = b.id)
        left join focco3i.titens_plan_conf bc on (bc.itpl_id = b.id and bc.tmasc_item_id = a.tmasc_item_id)
        where
        tipo_ord_dem LIKE 'OFP'
        and situacao = 1
        and plano_id = 16)))
        ord2 on (dd.itpl_id||dd.tmasc_item_id = ord2.itpl_id||ord2.tmasc_item_id and ord2.dt_ajustada = dd.dt_fim)
        inner join (select
        itpl_id,
        tmasc_item_id,
        num_ordem,
        dt_ajustada
            FROM
        (SELECT
             itpl_id,
            tmasc_item_id,
            num_ordem,
                  (SELECT MIN(data)
             FROM (
                 SELECT c.data
                 FROM focco3i.tcalendarios c
                 WHERE c.util_indus = 1
                   AND c.data >= dt_fim_mrp
                 ORDER BY c.data
                 OFFSET tempo_seg ROWS FETCH NEXT 1 ROWS ONLY
             )
            ) AS
             dt_ajustada
            FROM
             (select
        a.itpl_id,
        a.tmasc_item_id,
        a.num_ordem,
        perf.dt_fim_mrp,
        nvl((case when a.tmasc_item_id is null then b.tempo_seg else bc.tempo_seg end),0) tempo_seg
            FROM
        focco3i.tordens a
        inner join (select
        to_number(referencia) num_ordem,
        (case
        when mensagem like '%Cance%' then dt_fim          when mensagem is not null then to_date(to_char(reverse(substr(reverse(mensagem),2,10))),'dd/mm/yyyy')
        else dt_fim end)
        dt_fim_mrp,
        mensagem
        from
        focco3i.tperfil_itens
        where
        tipo_ord_dem in ('OFA','OFF','OFM')
        and plano_id = 16)
        perf on (perf.num_ordem = a.num_ordem)
        inner join focco3i.titens_planejamento b on (a.itpl_id = b.id)
        left join focco3i.titens_plan_conf bc on (bc.itpl_id = b.id and bc.tmasc_item_id = a.tmasc_item_id)
            WHERE
        a.final = 0
        union all
        select
        a.itpl_id,
        a.tmasc_item_id,
        to_number(a.referencia) num_ordem,
        a.dt_fim dt_fim_mrp,
        nvl((case when a.tmasc_item_id is null then b.tempo_seg else bc.tempo_seg end),0) tempo_seg
        from
        focco3i.tperfil_itens a
        inner join focco3i.titens_planejamento b on (a.itpl_id = b.id)
        left join focco3i.titens_plan_conf bc on (bc.itpl_id = b.id and bc.tmasc_item_id = a.tmasc_item_id)
        where
        tipo_ord_dem LIKE 'OFP'
        and situacao = 1
        and plano_id = 16)))
        ord1 on (dd.ordem_pai = ord1.num_ordem)
            connect by prior
        ord2.num_ordem = ord1.num_ordem
            start with
        ord1.num_ordem in (select distinct
        ord.num_ordem
            from
        (select
        a.itpl_id,
        a.tmasc_item_id,
        a.referencia,
        a.dt_fim
            from
         focco3i.tperfil_itens a
            where
         tipo_ord_dem like 'DC%'
        and a.plano_id = 16)
        dc
        inner join (select
        itpl_id,
        tmasc_item_id,
        num_ordem,
        dt_ajustada
            FROM
        (SELECT
             itpl_id,
            tmasc_item_id,
            num_ordem,
                  (SELECT MIN(data)
             FROM (
                 SELECT c.data
                 FROM focco3i.tcalendarios c
                 WHERE c.util_indus = 1
                   AND c.data >= dt_fim_mrp
                 ORDER BY c.data
                 OFFSET tempo_seg ROWS FETCH NEXT 1 ROWS ONLY
             )
            ) AS
             dt_ajustada
            FROM
             (select
        a.itpl_id,
        a.tmasc_item_id,
        a.num_ordem,
        perf.dt_fim_mrp,
        nvl((case when a.tmasc_item_id is null then b.tempo_seg else bc.tempo_seg end),0) tempo_seg
            FROM
        focco3i.tordens a
        inner join (select
        to_number(referencia) num_ordem,
        (case
        when mensagem like '%Cance%' then dt_fim          when mensagem is not null then to_date(to_char(reverse(substr(reverse(mensagem),2,10))),'dd/mm/yyyy')
        else dt_fim end)
        dt_fim_mrp,
        mensagem
        from
        focco3i.tperfil_itens
        where
        tipo_ord_dem in ('OFA','OFF','OFM')
        and plano_id = 16)
        perf on (perf.num_ordem = a.num_ordem)
        inner join focco3i.titens_planejamento b on (a.itpl_id = b.id)
        left join focco3i.titens_plan_conf bc on (bc.itpl_id = b.id and bc.tmasc_item_id = a.tmasc_item_id)
            WHERE
        a.final = 0
        union all
        select
        a.itpl_id,
        a.tmasc_item_id,
        to_number(a.referencia) num_ordem,
        a.dt_fim dt_fim_mrp,
        nvl((case when a.tmasc_item_id is null then b.tempo_seg else bc.tempo_seg end),0) tempo_seg
        from
        focco3i.tperfil_itens a
        inner join focco3i.titens_planejamento b on (a.itpl_id = b.id)
        left join focco3i.titens_plan_conf bc on (bc.itpl_id = b.id and bc.tmasc_item_id = a.tmasc_item_id)
        where
        tipo_ord_dem LIKE 'OFP'
        and situacao = 1
        and plano_id = 16)))
         ord on (dc.itpl_id||dc.tmasc_item_id = ord.itpl_id||ord.tmasc_item_id and dc.dt_fim = ord.dt_ajustada))
        union all
        select distinct
        ord.num_ordem ordem_zero,
        ord.num_ordem,
        to_char(ord.num_ordem) estrutura,
        0 nivel
            from
        (select
        a.itpl_id,
        a.tmasc_item_id,
        a.referencia,
        a.dt_fim
            from
         focco3i.tperfil_itens a
            where
         tipo_ord_dem like 'DC%'
        and a.plano_id = 16)
        dc
        inner join (select
        itpl_id,
        tmasc_item_id,
        num_ordem,
        dt_ajustada
            FROM
        (SELECT
             itpl_id,
            tmasc_item_id,
            num_ordem,
                  (SELECT MIN(data)
             FROM (
                 SELECT c.data
                 FROM focco3i.tcalendarios c
                 WHERE c.util_indus = 1
                   AND c.data >= dt_fim_mrp
                 ORDER BY c.data
                 OFFSET tempo_seg ROWS FETCH NEXT 1 ROWS ONLY
             )
            ) AS
             dt_ajustada
            FROM
             (select
        a.itpl_id,
        a.tmasc_item_id,
        a.num_ordem,
        perf.dt_fim_mrp,
        nvl((case when a.tmasc_item_id is null then b.tempo_seg else bc.tempo_seg end),0) tempo_seg
            FROM
        focco3i.tordens a
        inner join (select
        to_number(referencia) num_ordem,
        (case
        when mensagem like '%Cance%' then dt_fim          when mensagem is not null then to_date(to_char(reverse(substr(reverse(mensagem),2,10))),'dd/mm/yyyy')
        else dt_fim end)
        dt_fim_mrp,
        mensagem
        from
        focco3i.tperfil_itens
        where
        tipo_ord_dem in ('OFA','OFF','OFM')
        and plano_id = 16)
        perf on (perf.num_ordem = a.num_ordem)
        inner join focco3i.titens_planejamento b on (a.itpl_id = b.id)
        left join focco3i.titens_plan_conf bc on (bc.itpl_id = b.id and bc.tmasc_item_id = a.tmasc_item_id)
            WHERE
        a.final = 0
        union all
        select
        a.itpl_id,
        a.tmasc_item_id,
        to_number(a.referencia) num_ordem,
        a.dt_fim dt_fim_mrp,
        nvl((case when a.tmasc_item_id is null then b.tempo_seg else bc.tempo_seg end),0) tempo_seg
        from
        focco3i.tperfil_itens a
        inner join focco3i.titens_planejamento b on (a.itpl_id = b.id)
        left join focco3i.titens_plan_conf bc on (bc.itpl_id = b.id and bc.tmasc_item_id = a.tmasc_item_id)
        where
        tipo_ord_dem LIKE 'OFP'
        and situacao = 1
        and plano_id = 16)))
        ord on (dc.itpl_id||dc.tmasc_item_id = ord.itpl_id||ord.tmasc_item_id and dc.dt_fim = ord.dt_ajustada)
        order by
        ordem_zero asc,
        estrutura asc) ord
        inner join (select distinct
        b.num_pedido,
        f.cod_cli|| '-' ||m.nome_fan cliente,
        g.descricao representante,
        a.dt_entrega,
        ord_pdv.num_ordem
            from
        focco3i.titens_pdv a
        inner join focco3i.tpedidos_venda b on (b.id = a.pdv_id)
        inner join focco3i.titens_comercial c on (c.id = a.itcm_id)
        inner join focco3i.titens_empr d on (d.id = c.itempr_id)
        inner join focco3i.titens e on (e.id = d.item_id)
        inner join focco3i.titens_planejamento p on (d.id = p.itempr_id)
        inner join focco3i.titens_engenharia eng on (eng.itempr_id = d.id)
        inner join focco3i.tclientes f on (f.id = b.cli_id)
        inner join focco3i.trepresentantes g on (b.rep_id = g.id)
         left join focco3i.tmasc_item l on (a.tmasc_item_id = l.id)
        left join focco3i.testabelecimentos m on (f.est_id_fat = m.id)
        left join (select
        cp.id itempr_id_pai,
        df.id itpl_id
            from
        focco3i.tcad_est_ite a
        inner join focco3i.titens bp on (a.pai_id = bp.id)
        inner join focco3i.titens bf on (a.filho_id = bf.id)
        inner join focco3i.titens_empr cp on (bp.id = cp.item_id)
        inner join focco3i.titens_empr cf on (bf.id = cf.item_id)
        inner join focco3i.titens_planejamento dp on (cp.id = dp.itempr_id)
        inner join focco3i.titens_planejamento df on (cf.id = df.itempr_id))
        it_com on (d.id = it_com.itempr_id_pai)
        left join (select distinct
        dc.itpl_id,
        dc.tmasc_item_id,
        ord.num_ordem,
        dc.dt_fim
            from
        (select
        a.itpl_id,
        a.tmasc_item_id,
        a.referencia,
        a.dt_fim
            from
         focco3i.tperfil_itens a
            where
         tipo_ord_dem like 'DC%'
        and a.plano_id = 16)
        dc
        inner join (select
        itpl_id,
        tmasc_item_id,
        num_ordem,
        dt_ajustada
            FROM
        (SELECT
             itpl_id,
            tmasc_item_id,
            num_ordem,
                  (SELECT MIN(data)
             FROM (
                 SELECT c.data
                 FROM focco3i.tcalendarios c
                 WHERE c.util_indus = 1
                   AND c.data >= dt_fim_mrp
                 ORDER BY c.data
                 OFFSET tempo_seg ROWS FETCH NEXT 1 ROWS ONLY
             )
            ) AS
             dt_ajustada
            FROM
             (select
        a.itpl_id,
        a.tmasc_item_id,
        a.num_ordem,
        perf.dt_fim_mrp,
        nvl((case when a.tmasc_item_id is null then b.tempo_seg else bc.tempo_seg end),0) tempo_seg
            FROM
        focco3i.tordens a
        inner join (select
        to_number(referencia) num_ordem,
        (case
        when mensagem like '%Cance%' then dt_fim          when mensagem is not null then to_date(to_char(reverse(substr(reverse(mensagem),2,10))),'dd/mm/yyyy')
        else dt_fim end)
        dt_fim_mrp,
        mensagem
        from
        focco3i.tperfil_itens
        where
        tipo_ord_dem in ('OFA','OFF','OFM')
        and plano_id = 16)
        perf on (perf.num_ordem = a.num_ordem)
        inner join focco3i.titens_planejamento b on (a.itpl_id = b.id)
        left join focco3i.titens_plan_conf bc on (bc.itpl_id = b.id and bc.tmasc_item_id = a.tmasc_item_id)
            WHERE
        a.final = 0
        union all
        select
        a.itpl_id,
        a.tmasc_item_id,
        to_number(a.referencia) num_ordem,
        a.dt_fim dt_fim_mrp,
        nvl((case when a.tmasc_item_id is null then b.tempo_seg else bc.tempo_seg end),0) tempo_seg
        from
        focco3i.tperfil_itens a
        inner join focco3i.titens_planejamento b on (a.itpl_id = b.id)
        left join focco3i.titens_plan_conf bc on (bc.itpl_id = b.id and bc.tmasc_item_id = a.tmasc_item_id)
        where
        tipo_ord_dem LIKE 'OFP'
        and situacao = 1
        and plano_id = 16)))
        ord on (dc.itpl_id||dc.tmasc_item_id = ord.itpl_id||ord.tmasc_item_id and dc.dt_fim = ord.dt_ajustada))
        ord_pdv on (ord_pdv.itpl_id = (case when eng.tp_estrutura like 'C' then it_com.itpl_id else p.id end) and a.dt_entrega = ord_pdv.dt_fim)
            where
        b.pos_pdv = 'PE'              and b.tipo = 'PDV'
        and a.qtde_sldo > 0
        and a.sit_eng like 'LIB'
        and a.sit_proc like 'LIB'
        and a.sit_com like 'LIB')
        ped on (ord.ordem_zero = ped.num_ordem)
        order by
        ped.dt_entrega asc,
        ord.ordem_zero asc,
        ord.estrutura asc
        ) CONSOLE1 --BASE 1 DE DADOS (1 - Base console de ordens - atualizacao.xlsx)
         INNER JOIN
        (--PCP - Relatorio de ordens - Console 2
        select
         ord.tipo_ordem,
        ord.num_ordem,
        d.cod_item,
        d.desc_tecnica,
        e.mascara,
        ord.dt_liberacao,
        ord.dt_inicial,
        ord.dt_final,
        perf.mensagem,
        ord.qtde,
        operacao_pend,
        tempo_op_pendente,
        nvl(estq.sld_atual,0) sld_atual,
        disp.disp_demanda,
        i.cod rancho,
        g.cod_func||'-'||g.nome planejador,
        b.tempo_rep,
        b.tempo_seg,
        b.estq_seg,
        b.cons_medio,
        b.lote_min,
        b.lote_mult
        from
        (select
         itpl_id,
        id ordem_id,
        tipo_ordem,
        num_ordem,
        tmasc_item_id,
        dt_liberacao,
        dt_inicial,
        dt_final,
        qtde
        from
        focco3i.tordens
        where
        final = 0
        union all
        select
         itpl_id,
        id ordem_id,
        tipo_ordem,
        num_ordem,
        tmasc_item_id,
        dt_liberacao,
        dt_inicial,
        dt_final,
        qtde
        from
        focco3i.tordens
        where
        final = 1
        and dt_entrega >= sysdate -2
        union all
        select
         itpl_id,
        null ordem_id,
        tipo_ord_dem tipo_ordem,
        to_number(referencia) num_ordem,
        tmasc_item_id,
        null dt_liberacao,
        dt_inicio dt_inicial,
        dt_fim dt_final,
        qtde
        from
        focco3i.tperfil_itens
        where
        tipo_ord_dem like 'OFP'
        and plano_id = 16
        and situacao = 1)
        ord
        left join (select distinct
        a.num_ordem,
        a.qtde,
        a.qtde_pendente,
        a.dt_inicial,
        a.dt_final,
        LISTAGG(i.descricao,' | ') within group (order by a.num_ordem,g.seq) over (partition by a.num_ordem) operacao_pend,
        trunc (sum(g.tempo*qtde_pendente) over (partition by a.num_ordem),2) tempo_op_pendente          from
        focco3i.tordens a
        inner join focco3i.tordens_rot g on (g.ordem_id = a.id)
        inner join focco3i.toperacao i on (g.operacao_id = i.id)
            where
        a.final = 0
        and g.final = 0
        union all            select distinct
        a.num_ordem,
        a.qtde,
        a.qtde_pendente,
        a.dt_inicial,
        a.dt_final,
        LISTAGG(c.descricao,' | ') within group (order by a.num_ordem,b.seq) over (partition by a.num_ordem) operacao_pend,
        trunc (sum(b.tempo*a.qtde_pendente) over (partition by a.num_ordem),2) tempo_op_pendente            from
        (select id,to_number (referencia) num_ordem,qtde,qtde qtde_pendente,dt_inicio dt_inicial,dt_fim dt_final from focco3i.tperfil_itens where tipo_ord_dem like 'OF%' and situacao = 1 and plano_id = 16) a
        inner join focco3i.tmrp_roteiros b on (b.perfil_ite_id = a.id)
        inner join focco3i.toperacao c on (b.operacao_id = c.id))
        of_apt on (ord.num_ordem = of_apt.num_ordem)
        left join (SELECT
        d.id itpl_id,
        i.id tmasc_item_id,
        trunc(sum(a.sld_atual),2) sld_atual            FROM
        focco3i.testq a
        inner join focco3i.titens_estoque b on (a.itestq_id = b.id)
         inner join focco3i.titens_empr c on (b.itempr_id = c.id)
         inner join focco3i.titens_planejamento d on (d.itempr_id = c.id)
        inner join focco3i.talmoxarifados e on (a.almox_id = e.id)
        left join focco3i.tmasc_item i on (a.tmasc_item_id = i.id)                      where
        a.id in (select max(id) from focco3i.testq group by itestq_id,almox_id,tmasc_item_id)
        and e.estoque_disponivel = 1            group by
        d.id,
        i.id            having               sum(a.sld_atual) > 0)
        estq on (estq.itpl_id||estq.tmasc_item_id = ord.itpl_id||ord.tmasc_item_id)
        left join (select num_ordem,
        min(disponibilidade) disp_demanda            from (select
        dem.cod_demanda,
        dem.desc_demanda,
        dem.mascara,
        dem.qtde_demanda,
        (case when dem.qtde_demanda = 0 then 0 else sum(dem.qtde_demanda) over (partition by dem.cod_demanda,dem.mascara,dem.almox order by dem.cod_demanda asc,dem.almox,dem.dt_inicial asc,dem.num_ordem asc rows between unbounded preceding and current row) end) necessidade,
        nvl(estq.sld_atual,0) sld_atual,
        nvl(estq.sld_atual,0) - (case when dem.qtde_demanda = 0 then 0 else sum(dem.qtde_demanda) over (partition by dem.cod_demanda,dem.mascara,dem.almox order by dem.cod_demanda asc,dem.almox,dem.dt_inicial asc,dem.num_ordem asc rows between unbounded preceding and current row) end) disponibilidade,
        dem.tipo_ordem,
        dem.num_ordem,
        dem.dt_inicial,
        dem.dt_final,
        dem.qtde,
        dem.almox
        from
        (SELECT
        d1.cod_item cod_demanda,
        d1.desc_tecnica desc_demanda,
        i.mascara,
        a.qtde_pendente qtde_demanda,
        e.tipo_ordem,
        e.num_ordem,
        e.dt_inicial,
        e.dt_final,
        e.qtde,
        h.cod_almox||'-'||h.descricao almox            FROM
        focco3i.tdemandas a
         inner join focco3i.titens_planejamento b1 on (a.itpl_id = b1.ID)
        inner join focco3i.titens_empr c1 on (b1.itempr_id = c1.ID)
        inner join focco3i.titens_engenharia e1 on (e1.itempr_id = c1.ID)
        inner join focco3i.titens d1 on (c1.item_id = d1.ID)
        inner join focco3i.tordens e on (a.ordem_id = e.ID)
        inner join focco3i.titens_planejamento b on (e.itpl_id = b.ID)
        inner join focco3i.titens_empr c on (b.itempr_id = c.ID )
        inner join focco3i.titens d on (c.item_id = d.ID)
        inner join focco3i.tgrp_clas_ite g on (b1.grp_clas_id = g.ID)
        inner join focco3i.talmoxarifados h on (a.almox_id = h.id)
        left join focco3i.tmasc_item i on (a.tmasc_item_id = i.id)            WHERE
        c.empr_id = 1
        and a.final = 0
        and a.qtde_pendente > 0
        and g.cod_grp_ite like '1%'
            union all
        SELECT
        d1.cod_item cod_demanda,
        d1.desc_tecnica desc_demanda,
        i.mascara,
        a.qtde_pendente qtde_demanda,
        e.tipo_ordem,
        e.num_ordem,
        e.dt_inicial,
        e.dt_final,
        e.qtde,
        h.cod_almox||'-'||h.descricao almox            FROM
        focco3i.tdemandas a
         inner join focco3i.titens_planejamento b1 on (a.itpl_id = b1.ID)
        inner join focco3i.titens_empr c1 on (b1.itempr_id = c1.ID)
        inner join focco3i.titens_engenharia e1 on (e1.itempr_id = c1.ID)
        inner join focco3i.titens d1 on (c1.item_id = d1.ID)
        inner join focco3i.tordens e on (a.ordem_id = e.ID)
        inner join focco3i.titens_planejamento b on (e.itpl_id = b.ID)
        inner join focco3i.titens_empr c on (b.itempr_id = c.ID )
        inner join focco3i.titens d on (c.item_id = d.ID)
        inner join focco3i.tgrp_clas_ite g on (b1.grp_clas_id = g.ID)
        inner join focco3i.talmoxarifados h on (a.almox_id = h.id)
        left join focco3i.tmasc_item i on (a.tmasc_item_id = i.id)            WHERE
        c.empr_id = 1
        and a.final = 0
        and a.qtde_pendente > 0
        and g.cod_grp_ite like '4%'
        union all
        select
        d.cod_item cod_demanda,
        d.desc_tecnica desc_demanda,
        i.mascara,
        a.qtde qtde_demanda,
        a1.tipo_ord_dem tipo_ordem,
        to_number (substr(a.referencia,5,10)) num_ordem,
        a1.dt_inicio dt_inicial,
        a1.dt_fim dt_final,
        a1.qtde qtde_ordem,
        g.cod_almox||'-'||g.descricao almox            from
         focco3i.tperfil_itens a
        left join focco3i.tperfil_itens a1 on (to_char(substr(a.referencia,5,10))= to_char (a1.referencia))
        inner join focco3i.titens_planejamento b on (a.itpl_id = b.id)
        inner join focco3i.titens_empr c on (b.itempr_id = c.id)
        inner join focco3i.titens d on (c.item_id = d.id)
        left join focco3i.talmoxarifados g on (g.id = a.almox_id)
        left join focco3i.titens_planejamento b1 on (a1.itpl_id = b1.id)
        left join focco3i.titens_empr c1 on (b1.itempr_id = c1.id)
        left join focco3i.titens d1 on (c1.item_id = d1.id)
        inner join focco3i.tgrp_clas_ite f on (b.grp_clas_id = f.id)
        left join focco3i.tmasc_item i on (a.tmasc_item_id = i.id)            where
         a.tipo_ord_dem = 'DD'
        and a.plano_id = 16
        and a1.plano_id = 16
        and f.cod_grp_ite like '1%'
        and a1.situacao = 1            order by
        cod_demanda asc,
        dt_inicial asc,
        num_ordem asc)        dem
        left join (SELECT
        d.cod_item,
        d.desc_tecnica,
        f.mascara,
        e.cod_almox||'-'||e.descricao almox,
        a.sld_atual            FROM
        focco3i.testq a
        inner join focco3i.titens_estoque b on (a.itestq_id = b.id)
         inner join focco3i.titens_empr c on (b.itempr_id = c.id)
         inner join focco3i.titens d on (c.item_id = d.id)
        inner join focco3i.talmoxarifados e on (a.almox_id = e.id)
        left join focco3i.tmasc_item f on (a.tmasc_item_id = f.id)                     where
        a.id in (select max(id) from focco3i.testq group by itestq_id,almox_id,tmasc_item_id)
        and a.sld_atual > 0            order by
        d.desc_tecnica asc )
        estq on (dem.cod_demanda||dem.mascara = estq.cod_item||estq.mascara and dem.almox = estq.almox)
        where
        dem.almox not like '200-INTERNO PINTURA'
        order by
        dem.cod_demanda asc,
        dem.almox asc,
        dem.dt_inicial asc,
        dem.num_ordem asc)               group by
        num_ordem)        disp on (disp.num_ordem = ord.num_ordem)
        inner join focco3i.titens_planejamento b on (ord.itpl_id = b.id)
        inner join focco3i.titens_empr c on (b.itempr_id = c.id)
        inner join focco3i.titens d on (c.item_id = d.id)
        left join focco3i.tmasc_item e on (ord.tmasc_item_id = e.id)
        left join focco3i.titens_plan_func f on (f.itpl_id = b.id)         left join focco3i.tfuncionarios g on (f.func_id = g.id)
        left join focco3i.trancho_ordens h on (h.ordem_id = ord.ordem_id)
        left join focco3i.trancho i on (h.trancho_id = i.id)
        left join (select
        to_number(referencia) num_ordem,
        (case
        when mensagem like '%Cance%' then dt_fim          when mensagem is not null then to_date(to_char(reverse(substr(reverse(mensagem),2,10))),'dd/mm/yyyy')
        else dt_fim end)
        dt_fim_mrp,
        mensagem
        from
        focco3i.tperfil_itens
        where
        tipo_ord_dem in ('OFA','OFF','OFM')
        and plano_id = 16)
        perf on (perf.num_ordem = ord.num_ordem)
        ) CONSOLE2 /*PCP - Relatorio de ordens - Console 2*/ ON CONSOLE2.NUM_ORDEM=CONSOLE1.NUM_ORDEM
         )   CONSOLE1_MESCLADO_CONSOLE2    INNER JOIN
        (
        --PCP - Relatorio de ordens - Console 2
        select
         ord.tipo_ordem,
        ord.num_ordem,
        d.cod_item,
        d.desc_tecnica,
        e.mascara,
        ord.dt_liberacao,
        ord.dt_inicial,
        ord.dt_final,
        perf.mensagem,
        ord.qtde,
        operacao_pend,
        tempo_op_pendente,
        nvl(estq.sld_atual,0) sld_atual,
        disp.disp_demanda,
        i.cod rancho,
        g.cod_func||'-'||g.nome planejador,
        b.tempo_rep,
        b.tempo_seg,
        b.estq_seg,
        b.cons_medio,
        b.lote_min,
        b.lote_mult
        from
        (select
         itpl_id,
        id ordem_id,
        tipo_ordem,
        num_ordem,
        tmasc_item_id,
        dt_liberacao,
        dt_inicial,
        dt_final,
        qtde
        from
        focco3i.tordens
        where
        final = 0
        union all
        select
         itpl_id,
        id ordem_id,
        tipo_ordem,
        num_ordem,
        tmasc_item_id,
        dt_liberacao,
        dt_inicial,
        dt_final,
        qtde
        from
        focco3i.tordens
        where
        final = 1
        and dt_entrega >= sysdate -2
        union all
        select
         itpl_id,
        null ordem_id,
        tipo_ord_dem tipo_ordem,
        to_number(referencia) num_ordem,
        tmasc_item_id,
        null dt_liberacao,
        dt_inicio dt_inicial,
        dt_fim dt_final,
        qtde
        from
        focco3i.tperfil_itens
        where
        tipo_ord_dem like 'OFP'
        and plano_id = 16
        and situacao = 1)
        ord
        left join (select distinct
        a.num_ordem,
        a.qtde,
        a.qtde_pendente,
        a.dt_inicial,
        a.dt_final,
        LISTAGG(i.descricao,' | ') within group (order by a.num_ordem,g.seq) over (partition by a.num_ordem) operacao_pend,
        trunc (sum(g.tempo*qtde_pendente) over (partition by a.num_ordem),2) tempo_op_pendente          from
        focco3i.tordens a
        inner join focco3i.tordens_rot g on (g.ordem_id = a.id)
        inner join focco3i.toperacao i on (g.operacao_id = i.id)
            where
        a.final = 0
        and g.final = 0
        union all            select distinct
        a.num_ordem,
        a.qtde,
        a.qtde_pendente,
        a.dt_inicial,
        a.dt_final,
        LISTAGG(c.descricao,' | ') within group (order by a.num_ordem,b.seq) over (partition by a.num_ordem) operacao_pend,
        trunc (sum(b.tempo*a.qtde_pendente) over (partition by a.num_ordem),2) tempo_op_pendente            from
        (select id,to_number (referencia) num_ordem,qtde,qtde qtde_pendente,dt_inicio dt_inicial,dt_fim dt_final from focco3i.tperfil_itens where tipo_ord_dem like 'OF%' and situacao = 1 and plano_id = 16) a
        inner join focco3i.tmrp_roteiros b on (b.perfil_ite_id = a.id)
        inner join focco3i.toperacao c on (b.operacao_id = c.id))
        of_apt on (ord.num_ordem = of_apt.num_ordem)
        left join (SELECT
        d.id itpl_id,
        i.id tmasc_item_id,
        trunc(sum(a.sld_atual),2) sld_atual            FROM
        focco3i.testq a
        inner join focco3i.titens_estoque b on (a.itestq_id = b.id)
         inner join focco3i.titens_empr c on (b.itempr_id = c.id)
         inner join focco3i.titens_planejamento d on (d.itempr_id = c.id)
        inner join focco3i.talmoxarifados e on (a.almox_id = e.id)
        left join focco3i.tmasc_item i on (a.tmasc_item_id = i.id)                      where
        a.id in (select max(id) from focco3i.testq group by itestq_id,almox_id,tmasc_item_id)
        and e.estoque_disponivel = 1            group by
        d.id,
        i.id            having               sum(a.sld_atual) > 0)
        estq on (estq.itpl_id||estq.tmasc_item_id = ord.itpl_id||ord.tmasc_item_id)
        left join (select num_ordem,
        min(disponibilidade) disp_demanda            from (select
        dem.cod_demanda,
        dem.desc_demanda,
        dem.mascara,
        dem.qtde_demanda,
        (case when dem.qtde_demanda = 0 then 0 else sum(dem.qtde_demanda) over (partition by dem.cod_demanda,dem.mascara,dem.almox order by dem.cod_demanda asc,dem.almox,dem.dt_inicial asc,dem.num_ordem asc rows between unbounded preceding and current row) end) necessidade,
        nvl(estq.sld_atual,0) sld_atual,
        nvl(estq.sld_atual,0) - (case when dem.qtde_demanda = 0 then 0 else sum(dem.qtde_demanda) over (partition by dem.cod_demanda,dem.mascara,dem.almox order by dem.cod_demanda asc,dem.almox,dem.dt_inicial asc,dem.num_ordem asc rows between unbounded preceding and current row) end) disponibilidade,
        dem.tipo_ordem,
        dem.num_ordem,
        dem.dt_inicial,
        dem.dt_final,
        dem.qtde,
        dem.almox
        from
        (SELECT
        d1.cod_item cod_demanda,
        d1.desc_tecnica desc_demanda,
        i.mascara,
        a.qtde_pendente qtde_demanda,
        e.tipo_ordem,
        e.num_ordem,
        e.dt_inicial,
        e.dt_final,
        e.qtde,
        h.cod_almox||'-'||h.descricao almox            FROM
        focco3i.tdemandas a
         inner join focco3i.titens_planejamento b1 on (a.itpl_id = b1.ID)
        inner join focco3i.titens_empr c1 on (b1.itempr_id = c1.ID)
        inner join focco3i.titens_engenharia e1 on (e1.itempr_id = c1.ID)
        inner join focco3i.titens d1 on (c1.item_id = d1.ID)
        inner join focco3i.tordens e on (a.ordem_id = e.ID)
        inner join focco3i.titens_planejamento b on (e.itpl_id = b.ID)
        inner join focco3i.titens_empr c on (b.itempr_id = c.ID )
        inner join focco3i.titens d on (c.item_id = d.ID)
        inner join focco3i.tgrp_clas_ite g on (b1.grp_clas_id = g.ID)
        inner join focco3i.talmoxarifados h on (a.almox_id = h.id)
        left join focco3i.tmasc_item i on (a.tmasc_item_id = i.id)            WHERE
        c.empr_id = 1
        and a.final = 0
        and a.qtde_pendente > 0
        and g.cod_grp_ite like '1%'
            union all
        SELECT
        d1.cod_item cod_demanda,
        d1.desc_tecnica desc_demanda,
        i.mascara,
        a.qtde_pendente qtde_demanda,
        e.tipo_ordem,
        e.num_ordem,
        e.dt_inicial,
        e.dt_final,
        e.qtde,
        h.cod_almox||'-'||h.descricao almox            FROM
        focco3i.tdemandas a
         inner join focco3i.titens_planejamento b1 on (a.itpl_id = b1.ID)
        inner join focco3i.titens_empr c1 on (b1.itempr_id = c1.ID)
        inner join focco3i.titens_engenharia e1 on (e1.itempr_id = c1.ID)
        inner join focco3i.titens d1 on (c1.item_id = d1.ID)
        inner join focco3i.tordens e on (a.ordem_id = e.ID)
        inner join focco3i.titens_planejamento b on (e.itpl_id = b.ID)
        inner join focco3i.titens_empr c on (b.itempr_id = c.ID )
        inner join focco3i.titens d on (c.item_id = d.ID)
        inner join focco3i.tgrp_clas_ite g on (b1.grp_clas_id = g.ID)
        inner join focco3i.talmoxarifados h on (a.almox_id = h.id)
        left join focco3i.tmasc_item i on (a.tmasc_item_id = i.id)            WHERE
        c.empr_id = 1
        and a.final = 0
        and a.qtde_pendente > 0
        and g.cod_grp_ite like '4%'
        union all
        select
        d.cod_item cod_demanda,
        d.desc_tecnica desc_demanda,
        i.mascara,
        a.qtde qtde_demanda,
        a1.tipo_ord_dem tipo_ordem,
        to_number (substr(a.referencia,5,10)) num_ordem,
        a1.dt_inicio dt_inicial,
        a1.dt_fim dt_final,
        a1.qtde qtde_ordem,
        g.cod_almox||'-'||g.descricao almox            from
         focco3i.tperfil_itens a
        left join focco3i.tperfil_itens a1 on (to_char(substr(a.referencia,5,10))= to_char (a1.referencia))
        inner join focco3i.titens_planejamento b on (a.itpl_id = b.id)
        inner join focco3i.titens_empr c on (b.itempr_id = c.id)
        inner join focco3i.titens d on (c.item_id = d.id)
        left join focco3i.talmoxarifados g on (g.id = a.almox_id)
        left join focco3i.titens_planejamento b1 on (a1.itpl_id = b1.id)
        left join focco3i.titens_empr c1 on (b1.itempr_id = c1.id)
        left join focco3i.titens d1 on (c1.item_id = d1.id)
        inner join focco3i.tgrp_clas_ite f on (b.grp_clas_id = f.id)
        left join focco3i.tmasc_item i on (a.tmasc_item_id = i.id)            where
         a.tipo_ord_dem = 'DD'
        and a.plano_id = 16
        and a1.plano_id = 16
        and f.cod_grp_ite like '1%'
        and a1.situacao = 1            order by
        cod_demanda asc,
        dt_inicial asc,
        num_ordem asc)        dem
        left join (SELECT
        d.cod_item,
        d.desc_tecnica,
        f.mascara,
        e.cod_almox||'-'||e.descricao almox,
        a.sld_atual            FROM
        focco3i.testq a
        inner join focco3i.titens_estoque b on (a.itestq_id = b.id)
         inner join focco3i.titens_empr c on (b.itempr_id = c.id)
         inner join focco3i.titens d on (c.item_id = d.id)
        inner join focco3i.talmoxarifados e on (a.almox_id = e.id)
        left join focco3i.tmasc_item f on (a.tmasc_item_id = f.id)                     where
        a.id in (select max(id) from focco3i.testq group by itestq_id,almox_id,tmasc_item_id)
        and a.sld_atual > 0            order by
        d.desc_tecnica asc )
        estq on (dem.cod_demanda||dem.mascara = estq.cod_item||estq.mascara and dem.almox = estq.almox)
        where
        dem.almox not like '200-INTERNO PINTURA'
        order by
        dem.cod_demanda asc,
        dem.almox asc,
        dem.dt_inicial asc,
        dem.num_ordem asc)               group by
        num_ordem)        disp on (disp.num_ordem = ord.num_ordem)
        inner join focco3i.titens_planejamento b on (ord.itpl_id = b.id)
        inner join focco3i.titens_empr c on (b.itempr_id = c.id)
        inner join focco3i.titens d on (c.item_id = d.id)
        left join focco3i.tmasc_item e on (ord.tmasc_item_id = e.id)
        left join focco3i.titens_plan_func f on (f.itpl_id = b.id)         left join focco3i.tfuncionarios g on (f.func_id = g.id)
        left join focco3i.trancho_ordens h on (h.ordem_id = ord.ordem_id)
        left join focco3i.trancho i on (h.trancho_id = i.id)
        left join (select
        to_number(referencia) num_ordem,
        (case
        when mensagem like '%Cance%' then dt_fim          when mensagem is not null then to_date(to_char(reverse(substr(reverse(mensagem),2,10))),'dd/mm/yyyy')
        else dt_fim end)
        dt_fim_mrp,
        mensagem
        from
        focco3i.tperfil_itens
        where
        tipo_ord_dem in ('OFA','OFF','OFM')
        and plano_id = 16)
        perf on (perf.num_ordem = ord.num_ordem)
        )CONSOLE3 ON CONSOLE3.NUM_ORDEM= CONSOLE1_MESCLADO_CONSOLE2.ORDEM_ZERO
SQL;

    $resultados = $oracle->executeQuery($sql);
    if ($resultados === false) {
        throw new Exception('Erro ao executar query: ' . $oracle->getError());
    }

    $ordensMap = [];
    foreach ($resultados as $row) {
        $ordem = getField($row, ['NUM_ORDEM', 'ORDEM_ZERO']);
        if ($ordem === null) {
            continue;
        }

        $sequencia = intval(getField($row, ['NIVEL', 'SEQ', 'SEQUENCIA']) ?? 0);
        $operacao = getField($row, ['OPERACAO_PEND', 'OPERACAO', 'ESTRUTURA']);
        if ($operacao === null || $operacao === '') {
            $operacao = 'AGUARDAR';
        }

        $codigo = getField($row, ['COD_ITEM', 'MASCARA', 'COD']);
        $qnt = intval(getField($row, ['QTDE', 'QTDE_PENDENTE', 'QTDE_ORDEM']) ?? 0);
        $cliente = getField($row, ['CLIENTE']) ?? 'N/A';
        $data = formatDate(getField($row, ['DT_ENTREGA', 'DT_INICIAL', 'DT_FINAL']));

        $chave = $ordem . '-' . $sequencia . '-' . $operacao;
        if (!isset($ordensMap[$chave])) {
            $ordensMap[$chave] = [
                'data' => $data,
                'ordem' => (string) $ordem,
                'sequencia' => $sequencia,
                'operacao' => (string) $operacao,
                'codigo' => $codigo ?? 'N/A',
                'qnt' => $qnt,
                'cliente' => (string) $cliente,
                'data_finalizacao' => null
            ];
        }
    }

    $oracle->close();

    $response['success'] = true;
    $response['message'] = 'Ordens carregadas com sucesso';
    $response['data'] = array_values($ordensMap);
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log('[ORDENS_API] ' . $e->getMessage());
    http_response_code(500);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

function getField(array $row, array $keys) {
    foreach ($keys as $key) {
        if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
            return $row[$key];
        }
    }
    return null;
}

function formatDate($value) {
    if ($value === null || $value === '') {
        return null;
    }
    $ts = strtotime($value);
    if ($ts === false) {
        return null;
    }
    return date('d/m/Y', $ts);
}
?>
