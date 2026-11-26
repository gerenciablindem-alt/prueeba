# Esquema de la base de datos original (Supabase, proyecto blindem-dev)

Copia **solo de la estructura** (tablas, columnas y tipos) de la base Postgres de la app original, tomada el 2026-11-26 con una consulta de lectura sobre `information_schema`. **No contiene datos** de empresas ni de personas.

Convenciones: `!` = columna obligatoria (NOT NULL). Tipos de Postgres: int8/int4/int2 enteros, varchar/text texto, numeric decimal, bool, date, timestamp, json/jsonb.

Total: 81 tablas.

## Contenido

- [Afiliaciones (EPS/ARL/CCF/AFP)](#afiliaciones (eps/arl/ccf/afp))
- [Empresas, sedes y entidades](#empresas, sedes y entidades)
- [Incapacidades](#incapacidades)
- [Ventas, planes y tarifas](#ventas, planes y tarifas)
- [Cuotas, recibos y pagos](#cuotas, recibos y pagos)
- [Parámetros y tasas](#parámetros y tasas)
- [Créditos](#créditos)
- [Inmobiliaria](#inmobiliaria)
- [Usuarios, roles y multiempresa](#usuarios, roles y multiempresa)
- [Auditoría, integraciones y sistema](#auditoría, integraciones y sistema)
- [Comparación con Aportes Simple 2.0](#comparación-con-aportes-simple-20)

## Afiliaciones (EPS/ARL/CCF/AFP)

### `preafiliaciones`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| numero_radicado | varchar |  |
| empresa_id | int8 | sí |
| entidad_id | int8 |  |
| preafiliacion_tipo_id | int8 |  |
| user_id | int8 |  |
| tipo_documento | varchar | sí |
| numero_documento | varchar | sí |
| nombres_completos | varchar | sí |
| tipo_cotizante | varchar | sí |
| celular | varchar |  |
| direccion | varchar |  |
| correo | varchar |  |
| genero | varchar |  |
| fecha_nacimiento | date |  |
| pais_nacimiento | varchar |  |
| depto_nacimiento | varchar |  |
| ciudad_nacimiento | varchar |  |
| depto_servicio | varchar |  |
| municipio_servicio | varchar |  |
| ingreso_base | numeric |  |
| cargo | varchar |  |
| fecha_ingreso | date |  |
| fecha_retiro | date |  |
| beneficiarios | varchar |  |
| datos_especificos | jsonb |  |
| canal_envio | varchar |  |
| estado_envio | varchar | sí |
| enviado_at | timestamp |  |
| motivo_rechazo | text |  |
| rechazado_at | timestamp |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| deleted_at | timestamp |  |
| estado | varchar | sí |
| radicado_entidad | varchar |  |
| telefono | varchar |  |
| afiliado_id | int8 |  |
| codigo_entidad | varchar |  |
| referencia_cliente | varchar |  |
| gestion | varchar | sí |
| motivo_escalamiento | varchar |  |
| gestion_desde | timestamp |  |
| asignado_a | int8 |  |
| asignado_at | timestamp |  |
| motivo_correccion | text |  |
| venta_id | int8 |  |

### `preafiliaciones_tipos`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| codigo | varchar | sí |
| nombre | varchar | sí |
| descripcion | varchar |  |
| activo | bool | sí |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `historial_preafiliaciones`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| preafiliacion_id | int8 | sí |
| user_id | int8 |  |
| observacion | text |  |
| created_at | timestamp | sí |
| estado_anterior | varchar |  |
| estado_nuevo | varchar |  |

### `documentos_preafiliaciones`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| preafiliacion_id | int8 | sí |
| nombre_original | varchar | sí |
| ruta_storage | varchar | sí |
| mime_type | varchar | sí |
| tamano_bytes | int8 | sí |
| uploaded_by | int8 |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| tipo | varchar |  |

### `afiliaciones`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| numero_radicado | varchar | sí |
| preafiliacion_id | int8 |  |
| empresa_id | int8 | sí |
| entidad_id | int8 | sí |
| preafiliacion_tipo_id | int8 | sí |
| user_id | int8 | sí |
| tipo_documento | varchar | sí |
| numero_documento | varchar | sí |
| nombres_completos | varchar | sí |
| tipo_cotizante | varchar |  |
| genero | varchar |  |
| fecha_nacimiento | date |  |
| celular | varchar |  |
| correo | varchar |  |
| cargo | varchar |  |
| ingreso_base | numeric |  |
| fecha_ingreso | date | sí |
| fecha_radicacion | date | sí |
| beneficiarios | varchar |  |
| datos_especificos | jsonb |  |
| certificado_url | varchar |  |
| estado | varchar | sí |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| deleted_at | timestamp |  |
| radicado_entidad | varchar |  |
| afiliado_id | int8 |  |

### `afiliados`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| tipo_identificacion | varchar | sí |
| numero_identificacion | varchar | sí |
| correo | varchar | sí |
| clave | varchar | sí |
| acepta_terminos | bool | sí |
| autoriza_datos | bool | sí |
| recibir_info_celular | bool | sí |
| codigo_pais | varchar |  |
| celular | varchar |  |
| estado | varchar | sí |
| email_verification_token | varchar |  |
| email_verification_token_expires_at | timestamp |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| password_reset_code | varchar |  |
| password_reset_code_expires_at | timestamp |  |
| primer_nombre | varchar |  |
| segundo_nombre | varchar |  |
| primer_apellido | varchar |  |
| segundo_apellido | varchar |  |

### `intentos_automatizacion`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| preafiliacion_id | int8 | sí |
| user_id | int8 |  |
| proveedor | varchar | sí |
| resultado | varchar | sí |
| mensaje | text |  |
| evidencia_path | varchar |  |
| duracion_ms | int4 |  |
| created_at | timestamp | sí |
| origen | varchar | sí |

### `aportantes`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| tipo_documento | varchar | sí |
| numero_documento | varchar | sí |
| nombre_completo | varchar | sí |
| salario_base | numeric | sí |
| valor_aporte | numeric | sí |
| estado | varchar | sí |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `personas`

| Columna | Tipo | Obligatoria |
|---|---|---|
| numero_documento | varchar | sí |
| tipo_documento | varchar | sí |
| nombre_completo | varchar | sí |
| fecha_nacimiento | date |  |
| direccion | varchar |  |
| departamento | varchar | sí |
| ciudad | varchar | sí |
| telefono_1 | varchar | sí |
| telefono_2 | varchar |  |
| correo | varchar |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| fecha_expedicion | date |  |


## Empresas, sedes y entidades

### `empresas`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| nombre | varchar | sí |
| nit | varchar |  |
| descripcion | varchar |  |
| empresa_padre_id | int8 |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| codigo | varchar |  |
| tipo | varchar | sí |
| arl_name | varchar |  |
| status | varchar | sí |
| actividad_economica | varchar |  |
| codigo_actividad_economica | varchar |  |
| codigos_ciiu_disponibles | json |  |
| niveles_riesgo_disponibles | json |  |
| es_principal | bool | sí |
| tipo_identificacion | varchar | sí |
| departamento | varchar |  |
| ciudad | varchar |  |
| centros_trabajo_riesgo | json |  |
| codigo_afiliacion_axa | varchar |  |

### `empresa_entidades`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| empresa_id | int8 | sí |
| entidad_id | int8 | sí |
| fecha_vigencia_desde | date |  |
| activo | bool | sí |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| usuario | varchar |  |
| contrasena | varchar |  |
| usuario_secundario | varchar |  |
| tipo_documento | varchar |  |
| codigo_portal | varchar |  |

### `empresa_administraciones`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| empresa_id | int8 | sí |
| valor | int4 |  |
| vigente_desde | date | sí |
| registrado_por | int8 |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `entidades`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| nombre | varchar | sí |
| codigo | varchar |  |
| tipo | varchar | sí |
| canal_envio | varchar | sí |
| correo_envio | varchar |  |
| url_portal | varchar |  |
| activo | bool | sí |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| proveedor_bot | varchar |  |

### `correos_entidad_sede`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| sede_id | int8 | sí |
| entidad_id | int8 | sí |
| correo | varchar | sí |
| actualizado_por | int8 |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `cargo_empresa`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| empresa_id | int8 | sí |
| nombre | varchar | sí |
| nivel_riesgo | varchar | sí |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| codigo_ciiu | varchar | sí |
| grupo_ocupacion | varchar |  |
| tipo_ocupacion | varchar |  |

### `actividades_economicas`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| codigo_ciiu | varchar | sí |
| descripcion | text | sí |
| clase_riesgo | varchar |  |
| activo | bool | sí |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `asesores`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| empresa_id | int8 | sí |
| tipo_documento | varchar | sí |
| numero_documento | varchar | sí |
| nombre | varchar | sí |
| celular | varchar |  |
| correo | varchar |  |
| activo | bool | sí |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `asesor_tarifas`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| asesor_id | int8 | sí |
| valor_afiliacion | int4 | sí |
| vigente_desde | date | sí |
| registrado_por | int8 |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| valor_administracion | int4 |  |


## Incapacidades

### `incapacidades`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| cedula | varchar | sí |
| nombre_completo | varchar | sí |
| tipo_incapacidad | varchar | sí |
| EPS | varchar |  |
| empresa | varchar | sí |
| fecha_inicio | date | sí |
| fecha_fin | date | sí |
| fecha_radicacion | date |  |
| estado | varchar | sí |
| observaciones | text |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| deleted_at | timestamp |  |
| tipo_documento | varchar |  |
| email | varchar |  |
| celular | varchar |  |
| numero_radicado | varchar |  |
| ARL | varchar |  |
| codigo_cie10 | varchar |  |
| user_id | int8 |  |
| es_prorroga | bool |  |
| incapacidad_previa_id | int8 |  |
| dias_acumulados | int4 |  |
| empresa_id | int8 |  |

### `incapacidad_documentos`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| incapacidad_id | int8 | sí |
| nombre_display | varchar | sí |
| nombre_clave | varchar |  |
| url | text | sí |
| extension | varchar |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `incapacidad_seguimientos`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| incapacidad_id | int8 | sí |
| estado_anterior | varchar |  |
| estado_nuevo | varchar | sí |
| comentario | text |  |
| user_id | int8 |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `incapacidad_estadisticas`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| estado | varchar | sí |
| total | int4 | sí |
| actualizado_en | timestamp | sí |

### `cuentas_cobro`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int4 | sí |
| incapacidad_id | int4 |  |
| valor_incapacidad | numeric |  |
| impuesto_4_mil | numeric |  |
| costo_bancario | numeric |  |
| total_pagar | numeric |  |
| pdf_soporte | text |  |
| pdf_anexo | text |  |
| estado | varchar |  |
| created_at | timestamptz |  |
| updated_at | timestamptz |  |
| fecha_inicio | date |  |
| fecha_pago | date |  |
| descuento_cartera | numeric |  |
| consecutivo | int4 |  |
| deleted_at | timestamp |  |
| porcentaje_costo_bancario | numeric |  |
| fecha_ingreso_banco | date |  |

### `cie10`

| Columna | Tipo | Obligatoria |
|---|---|---|
| codigo | varchar | sí |
| descripcion | text | sí |


## Ventas, planes y tarifas

### `ventas`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| empresa_id | int8 | sí |
| sede_id | int8 | sí |
| plan_id | int8 | sí |
| tarifario_id | int8 | sí |
| nivel_riesgo | varchar | sí |
| eps | int4 | sí |
| afp | int4 | sí |
| ccf | int4 | sí |
| arl | int4 | sí |
| recargo | int4 | sí |
| precio_plan | int4 | sí |
| administracion | int4 | sí |
| valor_total | int4 | sí |
| asesor_id | int8 |  |
| tarifa_afiliacion | int4 |  |
| tipo_documento | varchar | sí |
| numero_documento | varchar | sí |
| nombre_cliente | varchar | sí |
| periodo_inicial | date | sí |
| estado | varchar | sí |
| user_id | int8 |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| tarifa_administracion | int4 |  |
| para_retiro_desde | date |  |
| periodo_retiro | date |  |

### `venta_conceptos`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| venta_id | int8 | sí |
| otro_concepto_id | int8 |  |
| nombre_concepto | varchar | sí |
| descripcion | varchar |  |
| valor | int4 | sí |
| recargo_planilla | bool | sí |
| desde_periodo | date | sí |
| hasta_periodo | date |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `planes`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| codigo | varchar | sí |
| nombre | varchar | sí |
| descripcion | text |  |
| incluye_eps | bool | sí |
| incluye_arl | bool | sí |
| incluye_afp | bool | sí |
| incluye_ccf | bool | sí |
| activo | bool | sí |
| orden | int2 | sí |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `tarifarios`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| anio | int2 | sí |
| vigente_desde | date | sí |
| vigente_hasta | date |  |
| smlv | int8 | sí |
| estado | varchar | sí |
| creado_por | int8 |  |
| publicado_por | int8 |  |
| publicado_at | timestamp |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `tarifario_planes`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| tarifario_id | int8 | sí |
| plan_id | int8 | sí |
| recargo | int4 | sí |
| motivo_recargo | varchar |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `tarifario_celdas`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| tarifario_id | int8 | sí |
| plan_id | int8 | sí |
| nivel_riesgo | varchar | sí |
| eps | int4 | sí |
| afp | int4 | sí |
| ccf | int4 | sí |
| arl | int4 | sí |
| recargo | int4 | sí |
| total_calculado | int4 | sí |
| total_ajustado | int4 |  |
| vendible | bool | sí |
| ajustado_por | int8 |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `otros_conceptos`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| nombre | varchar | sí |
| activo | bool | sí |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| codigo | varchar |  |
| periodicidad | varchar | sí |
| valor_sugerido | int4 |  |
| pide_descripcion | bool | sí |
| orden | int2 | sí |


## Cuotas, recibos y pagos

### `cuotas`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| venta_id | int8 | sí |
| periodo | date | sí |
| valor | int4 | sí |
| administracion | int4 | sí |
| fecha_limite | date | sí |
| estado | varchar | sí |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| tipo | varchar | sí |

### `cuota_conceptos`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| cuota_id | int8 | sí |
| otro_concepto_id | int8 |  |
| venta_concepto_id | int8 |  |
| nombre_concepto | varchar | sí |
| descripcion | varchar |  |
| valor_unitario | int4 | sí |
| cantidad | int2 | sí |
| valor_total | int4 | sí |
| origen | varchar | sí |
| user_id | int8 |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `generaciones_cuotas`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| periodo | date | sí |
| sede_id | int8 |  |
| origen | varchar | sí |
| user_id | int8 |  |
| estado | varchar | sí |
| generadas | int4 | sí |
| ya_existian | int4 | sí |
| omitidas | int4 | sí |
| detalle | json |  |
| iniciada_at | timestamp |  |
| terminada_at | timestamp |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `recibos`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| sede_id | int8 | sí |
| consecutivo | int4 | sí |
| numero | varchar | sí |
| cuota_id | int8 | sí |
| venta_id | int8 | sí |
| valor | int4 | sí |
| estado | varchar | sí |
| pdf_ruta | varchar |  |
| pdf_generado_at | timestamp |  |
| estado_correo | varchar |  |
| enviado_correo_at | timestamp |  |
| error_correo | varchar |  |
| enviado_whatsapp_at | timestamp |  |
| generado_por | int8 |  |
| pagado_at | timestamp |  |
| forma_pago | varchar |  |
| pdf_pagado_ruta | varchar |  |
| anulado_at | timestamp |  |
| anulado_por | int8 |  |
| motivo_anulacion | text |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| valor_pagado | int4 | sí |
| observacion_anulacion | varchar |  |

### `recibo_pagos`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| recibo_id | int8 | sí |
| sede_id | int8 | sí |
| valor | int4 | sí |
| fecha_pago | date | sí |
| medio | varchar | sí |
| cuenta_destino | varchar |  |
| referencia | varchar |  |
| soporte_ruta | varchar |  |
| observaciones | varchar |  |
| origen | varchar | sí |
| referencia_externa | varchar |  |
| estado | varchar | sí |
| anulado_at | timestamp |  |
| anulado_por | int8 |  |
| motivo_anulacion | varchar |  |
| observacion_anulacion | varchar |  |
| registrado_por | int8 |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| lote_id | int8 |  |

### `recibo_envios`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| recibo_id | int8 | sí |
| canal | varchar | sí |
| origen | varchar | sí |
| destino | varchar | sí |
| estado | varchar | sí |
| error | varchar |  |
| user_id | int8 |  |
| created_at | timestamp |  |

### `pago_lotes`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| sede_id | int8 | sí |
| valor_total | int4 | sí |
| fecha_pago | date | sí |
| medio | varchar | sí |
| cuenta_destino | varchar |  |
| referencia | varchar |  |
| soporte_ruta | varchar |  |
| observaciones | varchar |  |
| registrado_por | int8 |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `datos_recibo`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| empresa_id | int8 | sí |
| razon_social | varchar | sí |
| nombre_comercial | varchar |  |
| nit | varchar | sí |
| direccion | varchar | sí |
| telefono | varchar | sí |
| celular | varchar |  |
| correo | varchar | sí |
| logo_ruta | varchar |  |
| prefijo | varchar |  |
| numero_inicial | int4 | sí |
| nota_pie | text |  |
| cuentas | jsonb |  |
| actualizado_por | int8 |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| llave_pago | varchar |  |

### `bancos`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| nombre | varchar | sí |
| tipo | varchar |  |
| codigo_ach | varchar |  |
| activo | bool | sí |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `retiros`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| venta_id | int8 | sí |
| sede_id | int8 | sí |
| tipo | varchar | sí |
| periodo | date | sí |
| fecha_retiro | date | sí |
| dias | int2 | sí |
| ibc | int4 | sí |
| eps | int4 |  |
| afp | int4 |  |
| ccf | int4 |  |
| arl | int4 |  |
| valor_aportes | int4 | sí |
| valor_penalizacion | int4 | sí |
| cuota_id | int8 |  |
| motivo | varchar | sí |
| observacion | varchar |  |
| soporte_ruta | varchar |  |
| registrado_por | int8 |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |


## Parámetros y tasas

### `parametros_anuales`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| anio | int2 | sí |
| smlv | int8 | sí |
| pct_salud | numeric | sí |
| pct_salud_empleador | numeric | sí |
| pct_pension | numeric | sí |
| pct_ccf | numeric | sí |
| pct_fsp | numeric | sí |
| tasas_arl | jsonb | sí |
| norma_referencia | text |  |
| estado | varchar | sí |
| publicado_por | int8 |  |
| publicado_at | timestamp |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| mora_tramo1 | int4 | sí |
| mora_tramo2 | int4 | sí |
| penalizacion_retiro | int4 | sí |

### `tasas_ipc`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| anio | int2 | sí |
| porcentaje | numeric | sí |
| observacion | text |  |
| user_id | int8 |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `tasas_moratorias`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| periodo | varchar | sí |
| tasa_mensual | numeric | sí |
| tasa_corriente | numeric |  |
| observacion | text |  |
| user_id | int8 |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `tipos_documento`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| nombre | varchar | sí |
| extensiones | json |  |
| aplica_a | varchar | sí |
| created_at | timestamp |  |
| updated_at | timestamp |  |


## Créditos

### `lineas_credito`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| nombre | varchar | sí |
| activo | bool | sí |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `solicitudes_credito`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| numero_documento | varchar | sí |
| linea_credito_id | int8 | sí |
| monto_solicitado | int8 | sí |
| plazo_meses | int4 | sí |
| estado | varchar | sí |
| fecha_solicitud | date | sí |
| observaciones | text |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| codeudor | varchar |  |
| motivo_correccion | text |  |

### `analisis_credito`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| solicitud_id | int8 | sí |
| credito_id | int8 |  |
| puntaje_datacredito | int4 |  |
| resultado | varchar | sí |
| observaciones | text |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `creditos`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| solicitud_id | int8 | sí |
| monto_aprobado | int8 | sí |
| tasa_interes | numeric |  |
| plazo_meses | int4 | sí |
| estado | varchar | sí |
| fecha_inicio | date |  |
| fecha_fin | date |  |
| saldo_pendiente | int8 |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| dias_mora | int4 | sí |
| tasa_moratoria | numeric |  |
| interes_moratorio_acumulado | int8 | sí |
| periodo_gracia_duracion | int4 |  |
| periodo_gracia_unidad | varchar |  |
| fecha_inicio_original | date |  |

### `documentos_credito`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| solicitud_id | int8 |  |
| tipo_documento | varchar |  |
| nombre_archivo | varchar | sí |
| ruta_archivo | varchar | sí |
| estado | varchar | sí |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| tipo_documento_id | int8 |  |
| inmueble_id | int4 |  |
| pago_id | int8 |  |

### `historial_solicitudes_credito`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| solicitud_id | int8 | sí |
| documento_id | int8 |  |
| user_id | int8 |  |
| accion | varchar | sí |
| estado_anterior | varchar |  |
| estado_nuevo | varchar |  |
| motivo | text |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |


## Inmobiliaria

### `inmueble`

| Columna | Tipo | Obligatoria |
|---|---|---|
| codigo_inmueble | int4 | sí |
| tipo | varchar | sí |
| unidad | varchar |  |
| piso | varchar |  |
| zona | varchar | sí |
| direccion | varchar | sí |
| area | int4 | sí |
| valor_canon | int4 | sí |
| fecha_predial | date | sí |
| caracteristicas | varchar |  |
| parqueadero | bool |  |
| deposito | bool |  |
| terraza | bool |  |
| agua | bool |  |
| energia | bool |  |
| gas | bool |  |
| internet | bool |  |
| fecha_corte_agua | date |  |
| fecha_corte_energia | date |  |
| fecha_corte_gas | date |  |
| fecha_corte_internet | date |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| clasificacion_inmueble | clasificacion_inmueble | sí |

### `contratos`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id_contrato | int4 | sí |
| codigo_inmueble | int4 | sí |
| documento_id | int8 |  |
| inicio | date | sí |
| finalizacion | date | sí |
| valor_canon | int4 | sí |
| condiciones | varchar |  |
| fecha_solicitud | date |  |
| estado | varchar | sí |
| dia_pago | int4 | sí |
| dias_gracia | int4 |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| numero_documento | varchar | sí |

### `pagos`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| id_contrato | int4 | sí |
| fecha_vencimiento | date |  |
| fecha_pago | date |  |
| valor_pagado | int4 |  |
| dias_mora | int4 |  |
| estado | varchar |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| tasa_mora | numeric | sí |
| valor_canon | int4 |  |
| es_prorata | bool | sí |

### `abonos`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| id_contrato | int4 | sí |
| fecha_pago | date | sí |
| valor | int4 | sí |
| observacion | varchar |  |
| documento_id | int8 |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `cargos_adicionales`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| pago_id | int4 | sí |
| tipo | varchar | sí |
| descripcion | varchar |  |
| valor | int4 | sí |
| fecha | date | sí |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `cuentas_cobro_inmobiliaria`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| consecutivo | varchar | sí |
| id_contrato | int4 | sí |
| pago_id | int8 |  |
| concepto | varchar | sí |
| valor | int4 | sí |
| fecha_emision | date | sí |
| estado | varchar | sí |
| documento_id | int8 |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `facturas_electronicas`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| numero_factura | varchar | sí |
| id_contrato | int4 | sí |
| pago_id | int8 |  |
| fecha_factura | date | sí |
| concepto | varchar | sí |
| valor | int4 | sí |
| documento_id | int8 |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |


## Usuarios, roles y multiempresa

### `users`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| name | varchar | sí |
| email | varchar | sí |
| email_verified_at | timestamp |  |
| password | varchar | sí |
| remember_token | varchar |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| empresa_id | int8 |  |
| estado | varchar | sí |
| cedula | varchar |  |
| tipo_documento | varchar |  |

### `roles`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| nombre | varchar | sí |
| descripcion | varchar |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| nivel | int4 | sí |
| permisos_base | jsonb |  |
| empresa_id | int8 |  |
| rol_base_id | int8 |  |
| etiqueta | varchar |  |
| alcance_datos | varchar | sí |
| restringido | bool | sí |
| portal | varchar | sí |

### `usuario_permisos`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| user_id | int8 | sí |
| rol_id | int8 | sí |
| empresa_id | int8 | sí |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| modulos_adicionales | jsonb |  |
| permisos_denegados | jsonb |  |

### `usuario_empresa`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| usuario_id | int8 | sí |
| empresa_id | int8 | sí |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `usuario_tokens`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| user_id | int8 | sí |
| token | text | sí |
| generado_en | timestamp |  |
| expira_en | timestamp |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| activo | bool | sí |
| ultimo_uso_en | timestamp |  |
| creado_por | int8 |  |
| revocado_en | timestamp |  |
| revocado_por | int8 |  |

### `admin_tokens`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | uuid | sí |
| tenant_id | uuid | sí |
| token_hash | varchar | sí |
| expires_at | timestamp | sí |
| created_at | timestamp | sí |

### `tenants`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | uuid | sí |
| slug | varchar | sí |
| company_name | varchar | sí |
| company_subtitle | varchar |  |
| footer_text | varchar |  |
| logo_url | varchar |  |
| primary_color | varchar | sí |
| accent_color | varchar | sí |
| api_base_url | varchar |  |
| api_nit | varchar |  |
| api_document_type | varchar |  |
| api_document | varchar |  |
| api_password | text |  |
| api_secret_key | text |  |
| api_company | varchar |  |
| is_active | bool | sí |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `password_reset_tokens`

| Columna | Tipo | Obligatoria |
|---|---|---|
| email | varchar | sí |
| token | varchar | sí |
| created_at | timestamp |  |


## Auditoría, integraciones y sistema

### `audit_log`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| tabla | text | sí |
| registro_id | text | sí |
| accion | text | sí |
| datos_anteriores | jsonb |  |
| datos_nuevos | jsonb |  |
| usuario | uuid |  |
| fecha | timestamptz |  |

### `activity_log`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| log_name | varchar |  |
| description | text | sí |
| subject_type | varchar |  |
| subject_id | int8 |  |
| event | varchar |  |
| causer_type | varchar |  |
| causer_id | int8 |  |
| properties | json |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |
| batch_uuid | uuid |  |

### `notifications`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | uuid | sí |
| type | varchar | sí |
| notifiable_type | varchar | sí |
| notifiable_id | int8 | sí |
| data | text | sí |
| read_at | timestamp |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |

### `chat_state`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| telefono | text | sí |
| estado | text | sí |
| created_at | timestamptz | sí |
| updated_at | timestamptz | sí |

### `configuracion_api`

| Columna | Tipo | Obligatoria |
|---|---|---|
| clave | varchar | sí |
| valor | text |  |
| descripcion | text |  |
| updated_at | timestamp |  |

### `cache`

| Columna | Tipo | Obligatoria |
|---|---|---|
| key | varchar | sí |
| value | text | sí |
| expiration | int4 | sí |

### `cache_locks`

| Columna | Tipo | Obligatoria |
|---|---|---|
| key | varchar | sí |
| owner | varchar | sí |
| expiration | int4 | sí |

### `jobs`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| queue | varchar | sí |
| payload | text | sí |
| attempts | int2 | sí |
| reserved_at | int4 |  |
| available_at | int4 | sí |
| created_at | int4 | sí |

### `job_batches`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | varchar | sí |
| name | varchar | sí |
| total_jobs | int4 | sí |
| pending_jobs | int4 | sí |
| failed_jobs | int4 | sí |
| failed_job_ids | text | sí |
| options | text |  |
| cancelled_at | int4 |  |
| created_at | int4 | sí |
| finished_at | int4 |  |

### `failed_jobs`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int8 | sí |
| uuid | varchar | sí |
| connection | text | sí |
| queue | text | sí |
| payload | text | sí |
| exception | text | sí |
| failed_at | timestamp | sí |

### `sessions`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | varchar | sí |
| user_id | int8 |  |
| ip_address | varchar |  |
| user_agent | text |  |
| payload | text | sí |
| last_activity | int4 | sí |

### `migrations`

| Columna | Tipo | Obligatoria |
|---|---|---|
| id | int4 | sí |
| migration | varchar | sí |
| batch | int4 | sí |

### `telescope_entries`

| Columna | Tipo | Obligatoria |
|---|---|---|
| sequence | int8 | sí |
| uuid | uuid | sí |
| batch_id | uuid | sí |
| family_hash | varchar |  |
| should_display_on_index | bool | sí |
| type | varchar | sí |
| content | text | sí |
| created_at | timestamp |  |

### `telescope_entries_tags`

| Columna | Tipo | Obligatoria |
|---|---|---|
| entry_uuid | uuid | sí |
| tag | varchar | sí |

### `telescope_monitoring`

| Columna | Tipo | Obligatoria |
|---|---|---|
| tag | varchar | sí |

## Comparación con Aportes Simple 2.0

| Módulo de la app original | Estado en Aportes Simple 2.0 |
|---|---|
| Preafiliaciones, afiliaciones, historial y documentos | Cubierto (`preafiliaciones`, `afiliados`, `documentos`, `auditorias`). Faltan campos como `gestion`, `asignado_a`, `motivo_escalamiento`, `radicado_entidad`, `estado_envio`, `canal_envio`. |
| Envío automático a entidades (`intentos_automatizacion`, `entidades.proveedor_bot`) | No existe. Hay que construirlo. |
| Empresas, entidades y cargos | Cubierto. Faltan empresa padre, CIIU, niveles de riesgo, tarifas de administración y correos por sede y entidad. |
| Asesores y sus tarifas | No existe. |
| Incapacidades, seguimientos, documentos y CIE-10 | Cubierto, con calculadora. Faltan el catálogo CIE-10 y las **cuentas de cobro** (4x1000, costo bancario, descuento de cartera). |
| Ventas, planes y tarifarios por nivel de riesgo | No existe. |
| Cuotas mensuales, recibos, pagos, lotes y retiros | No existe. |
| Parámetros anuales (SMLV, % salud, pensión, CCF, ARL, mora) | Parcial: solo existe el salario mínimo en `configuraciones`. |
| Créditos (solicitudes, análisis, créditos) | No existe. |
| Inmobiliaria (inmuebles, contratos, pagos, facturas) | No existe. |
| Roles con permisos por módulo y multiempresa (`tenants`) | Parcial: 3 roles fijos (admin, operador, empresa). |
| Traslados | Existe en la app nueva. No aparece como tabla en la original. |

### Tablas con datos sensibles

Estas tablas guardan claves o tokens. Al migrar datos **no se copian** y se vuelven a crear en la app nueva: `empresa_entidades.contrasena`, `afiliados.clave`, `tenants.api_password`, `tenants.api_secret_key`, `admin_tokens`, `usuario_tokens`, `configuracion_api`, `users.password`.
