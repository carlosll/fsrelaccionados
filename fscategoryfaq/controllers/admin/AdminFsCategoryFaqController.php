<?php
/**
 * FS Category FAQ SEO — Controlador Back Office
 *
 * Gestiona el CRUD completo de FAQs: listar, filtrar, crear, editar,
 * activar/desactivar, eliminar y ordenar.
 *
 * Patrones tomados de AdminController (PS 8.2.7):
 * - HelperList con filtros por tipo de entidad
 * - HelperForm con selectores dinámicos (categoría/CMS/fabricante)
 * - Breadcrumbs y botones de toolbar
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'fs_category_faq/classes/FsCategoryFaq.php';

use FSCategoryFaq\FsCategoryFaq as FaqModel;

class AdminFsCategoryFaqController extends ModuleAdminController
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        // 1. Configuración base — necesaria para el constructor padre
        $this->table = 'fs_category_faq';
        $this->className = FaqModel::class;
        $this->identifier = 'id_faq';
        $this->lang = true;
        $this->bootstrap = true;

        // 2. Constructor padre — inicializa traductor, contexto, módulo, etc.
        parent::__construct();

        // 3. Configuración que depende del traductor (ya inicializado)
        $this->list_id = 'fs_category_faq';
        $this->list_simple_header = false;
        $this->multishop_context = Shop::CONTEXT_SHOP;
        $this->position_identifier = 'position';
        $this->orderBy = 'position';
        $this->orderWay = 'ASC';

        // Acciones en línea
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        // Acciones en lote (checkbox "seleccionar todas" + desplegable)
        $this->bulk_actions = [
            'enableSelection' => [
                'text' => $this->trans('Activar seleccionados', [], 'Modules.Fs_category_faq.Admin'),
                'icon' => 'icon-check',
            ],
            'disableSelection' => [
                'text' => $this->trans('Desactivar seleccionados', [], 'Modules.Fs_category_faq.Admin'),
                'icon' => 'icon-remove',
            ],
            'delete' => [
                'text' => $this->trans('Eliminar seleccionados', [], 'Modules.Fs_category_faq.Admin'),
                'confirm' => $this->trans('¿Eliminar las FAQs seleccionadas?', [], 'Modules.Fs_category_faq.Admin'),
                'icon' => 'icon-trash',
            ],
        ];

        // Columnas del listado
        $this->fields_list = [
            'id_faq' => [
                'title' => $this->trans('ID', [], 'Modules.Fs_category_faq.Admin'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'search' => false,
            ],
            'entity_type_label' => [
                'title' => $this->trans('Tipo', [], 'Modules.Fs_category_faq.Admin'),
                'type' => 'select',
                'filter_key' => 'a!entity_type',
                'list' => [
                    'category' => $this->trans('Categoría', [], 'Modules.Fs_category_faq.Admin'),
                    'home' => $this->trans('Inicio', [], 'Modules.Fs_category_faq.Admin'),
                    'cms' => $this->trans('CMS', [], 'Modules.Fs_category_faq.Admin'),
                    'manufacturer' => $this->trans('Fabricante', [], 'Modules.Fs_category_faq.Admin'),
                ],
            ],
            'entity_name' => [
                'title' => $this->trans('Entidad', [], 'Modules.Fs_category_faq.Admin'),
                'type' => 'select',
                'filter_key' => 'a!entity_id',
                'list' => $this->buildCategoryFilterOptions(),
            ],
            'question' => [
                'title' => $this->trans('Pregunta', [], 'Modules.Fs_category_faq.Admin'),
                'filter_key' => 'b!question',
            ],
            'active' => [
                'title' => $this->trans('Activo', [], 'Modules.Fs_category_faq.Admin'),
                'active' => 'status',
                'type' => 'bool',
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'filter_key' => 'a!active',
            ],
            'position' => [
                'title' => $this->trans('Posición', [], 'Modules.Fs_category_faq.Admin'),
                'align' => 'center',
                'class' => 'fixed-width-md',
                'position' => 'position',
            ],
        ];

        // CSS para listado y formulario
        $this->addCSS($this->module->getPathUri() . 'views/css/admin.css');
    }

    // ---------------------------------------------------------------
    //  LISTADO
    // ---------------------------------------------------------------

    /**
     * Fuerza la columna de checkboxes en el listado incluso cuando
     * hasBulkActions() del HelperList podría ocultarla (ej: 1 sola fila).
     *
     * {@inheritdoc}
     */
    public function setHelperDisplay(Helper $helper): void
    {
        parent::setHelperDisplay($helper);
        if ($helper instanceof HelperList) {
            $helper->force_show_bulk_actions = true;
        }
    }

    // ---------------------------------------------------------------
    //  LISTADO (continuación)
    // ---------------------------------------------------------------

    /**
     * {@inheritdoc}
     */
    public function initPageHeaderToolbar(): void
    {
        // Botón de añadir nuevo FAQ
        $this->page_header_toolbar_btn['new_faq'] = [
            'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
            'desc' => $this->trans('Añadir nueva FAQ', [], 'Modules.Fs_category_faq.Admin'),
            'icon' => 'process-icon-new',
        ];

        // Enlace a la configuración del módulo
        $this->page_header_toolbar_btn['module_config'] = [
            'href' => $this->context->link->getAdminLink('AdminModules', true, [], [
                'configure' => 'fs_category_faq',
            ]),
            'desc' => $this->trans('Configuración del módulo', [], 'Modules.Fs_category_faq.Admin'),
            'icon' => 'process-icon-cogs',
        ];

        // Importar FAQs desde archivo JSON
        $this->page_header_toolbar_btn['import_faqs'] = [
            'href' => self::$currentIndex . '&action=import_faqs&token=' . $this->token,
            'desc' => $this->trans('Importar FAQs', [], 'Modules.Fs_category_faq.Admin'),
            'icon' => 'process-icon-upload',
        ];

        // Exportar FAQs a archivo JSON
        $this->page_header_toolbar_btn['export_faqs'] = [
            'href' => self::$currentIndex . '&action=export_faqs&token=' . $this->token,
            'desc' => $this->trans('Exportar FAQs', [], 'Modules.Fs_category_faq.Admin'),
            'icon' => 'process-icon-download',
        ];

        parent::initPageHeaderToolbar();
    }

    /**
     * {@inheritdoc}
     */
    public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
    {
        // Filtro por tienda (necesario porque la tabla no usa el patrón _shop)
        $idShop = (int) $this->context->shop->id;
        $this->_where = 'AND a.id_shop = ' . $idShop;

        // El filtro "Entidad" del listado solo ofrece categorías (ver
        // buildCategoryFilterOptions()), pero filtra sobre entity_id, que
        // es un entero que también usan cms/manufacturer con su propio
        // autoincremental. Sin exigir además entity_type = 'category', un
        // id_cms o id_manufacturer que coincida numéricamente con la
        // categoría elegida se colaría en el listado filtrado.
        $categoryFilterId = $this->getCategoryFilterValue();
        if ($categoryFilterId > 0) {
            $this->_where .= ' AND a.entity_type = \'category\' AND a.entity_id = ' . $categoryFilterId;
        }

        // El padre construye la consulta con a.*, b.* y aplica filtros desde cookies
        parent::getList($id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop);

        // Enriquecer cada fila con datos extra (nombre de entidad, tipo de entidad)
        foreach ($this->_list as &$row) {
            $entityType = $row['entity_type'] ?? 'category';
            $entityId = (int) ($row['entity_id'] ?? 0);

            $row['entity_type_label'] = $this->getEntityTypeLabel($entityType);
            $row['entity_name'] = FaqModel::getEntityName($entityType, $entityId);
        }
    }

    /**
     * Lee el valor actualmente aplicado del filtro "Entidad" (categoría),
     * ya sea recién enviado por el usuario o persistido en la cookie de
     * una búsqueda anterior — igual que hace internamente HelperList.
     */
    private function getCategoryFilterValue(): int
    {
        $filterName = $this->table . 'Filter_entity_name';
        $value = Tools::getValue($filterName);

        if ($value === false || $value === '') {
            $value = $this->context->cookie->{$filterName} ?? 0;
        }

        return (int) $value;
    }

    // ---------------------------------------------------------------
    //  IMPORTAR / EXPORTAR
    // ---------------------------------------------------------------

    /**
     * {@inheritdoc}
     *
     * Intercepta las acciones de importación y exportación antes del
     * renderizado normal (lista o formulario).
     */
    public function initContent(): void
    {
        // Exportar: generar JSON y descargar directamente
        if (Tools::getValue('action') === 'export_faqs') {
            $this->processExportFaqs();
            return;
        }

        // Importar: mostrar formulario de subida de archivo
        if (Tools::getValue('action') === 'import_faqs') {
            // Al entrar limpiamente (GET, no POST), descartamos errores
            // residuales de ejecuciones anteriores que quedaron en cookie
            if (!Tools::isSubmit('submitImportFaqs')) {
                $this->errors = [];
            }
            $this->initPageHeaderToolbar();
            $this->content .= $this->renderImportForm();
        }

        parent::initContent();
    }

    /**
     * Renderiza el formulario de importación como HTML Bootstrap.
     *
     * No usamos HelperForm aquí porque es un formulario de una sola acción
     * (file upload) sin campos persistentes. Un panel Bootstrap directo es
     * más limpio y mantenible.
     */
    private function renderImportForm(): string
    {
        $action = self::$currentIndex . '&action=import_faqs&token=' . $this->token;
        $backUrl = self::$currentIndex . '&token=' . $this->token;

        // Si venimos de procesar una importación, los mensajes de
        // confirmación/error ya están en $this->confirmations y
        // $this->errors — parent::initContent() los renderiza.

        return '
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-upload"></i> '
                . $this->trans('Importar FAQs desde archivo JSON', [], 'Modules.Fs_category_faq.Admin') . '
            </div>
            <div class="panel-body">
                <div class="alert alert-info">
                    <p>'
                . $this->trans('Selecciona un archivo .json generado por DinoRank API u otra herramienta compatible con el formato de importación del módulo.', [], 'Modules.Fs_category_faq.Admin') . '</p>
                    <p><strong>' . $this->trans('Formato esperado:', [], 'Modules.Fs_category_faq.Admin') . '</strong></p>
                    <pre>{
  "faqs": [
    {
      "entity_type": "category",
      "entity_id": 19,
      "question": "texto de la pregunta",
      "answer": "&lt;p&gt;HTML de la respuesta&lt;/p&gt;",
      "active": true
    }
  ]
}</pre>
                    <p>'
                . $this->trans('El archivo no puede superar los 5 MB.', [], 'Modules.Fs_category_faq.Admin') . '</p>
                    <p>'
                . $this->trans('Las FAQs se importan en el idioma por defecto de la tienda y con la posición autoasignada.', [], 'Modules.Fs_category_faq.Admin') . '</p>
                </div>

                <form action="' . $action . '" method="post" enctype="multipart/form-data" class="form-horizontal">
                    <div class="form-group">
                        <label for="import_file" class="control-label col-lg-3">'
                . $this->trans('Archivo JSON', [], 'Modules.Fs_category_faq.Admin') . '
                        </label>
                        <div class="col-lg-9">
                            <input type="file" name="import_file" id="import_file"
                                   accept=".json,application/json" required="required"
                                   class="form-control" />
                            <p class="help-block">'
                . $this->trans('Archivo .json con las FAQs a importar.', [], 'Modules.Fs_category_faq.Admin') . '
                            </p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="import_duplicates" class="control-label col-lg-3">'
                . $this->trans('Si la pregunta ya existe en esa página', [], 'Modules.Fs_category_faq.Admin') . '
                        </label>
                        <div class="col-lg-9">
                            <select name="import_duplicates" id="import_duplicates" class="form-control">
                                <option value="duplicate_add" selected="selected">'
                . $this->trans('Añadir de todas formas (puede crear duplicados)', [], 'Modules.Fs_category_faq.Admin') . '</option>
                                <option value="duplicate_skip">'
                . $this->trans('Saltar pregunta (no importar la repetida, conservar la existente)', [], 'Modules.Fs_category_faq.Admin') . '</option>
                                <option value="duplicate_update">'
                . $this->trans('Actualizar respuesta (sobrescribir la respuesta y estado de la FAQ existente)', [], 'Modules.Fs_category_faq.Admin') . '</option>
                            </select>
                            <p class="help-block">'
                . $this->trans('Una pregunta se considera duplicada cuando ya existe otra con el mismo texto exacto en la misma página (misma categoría, CMS, etc.).', [], 'Modules.Fs_category_faq.Admin') . '
                            </p>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-3 col-lg-9">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="import_replace" value="1" />
                                    <strong>'
                . $this->trans('Reemplazar FAQs existentes en las mismas páginas', [], 'Modules.Fs_category_faq.Admin') . '</strong>
                                </label>
                                <p class="help-block" style="color:#c0392b;">'
                . $this->trans('⚠️ Se eliminarán solo las FAQs que estén en las mismas páginas (categoría, CMS, etc.) que las del archivo. Las FAQs de otras páginas no se tocan. Por ejemplo: si importas FAQs de la categoría "Generadores" y de "Inicio", se borrarán las FAQs actuales de esas dos páginas, pero las FAQs de otras categorías se conservan intactas. Usa Exportar para hacer una copia de seguridad antes.', [], 'Modules.Fs_category_faq.Admin') . '
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-3 col-lg-9">
                            <button type="submit" name="submitImportFaqs" class="btn btn-primary">
                                <i class="icon-upload"></i> '
                . $this->trans('Importar', [], 'Modules.Fs_category_faq.Admin') . '
                            </button>
                            <a href="' . $backUrl . '" class="btn btn-default">
                                <i class="icon-arrow-left"></i> '
                . $this->trans('Volver al listado', [], 'Modules.Fs_category_faq.Admin') . '
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>';
    }

    /**
     * Exporta todas las FAQs de la tienda actual como descarga JSON.
     *
     * Genera un archivo .json con todas las FAQs (todos los idiomas
     * disponibles) y lo envía al navegador como descarga.
     */
    protected function processExportFaqs(): void
    {
        $idShop = (int) $this->context->shop->id;
        $rows = FaqModel::getAllWithTranslations($idShop);

        $exportFaqs = [];
        foreach ($rows as $row) {
            $exportFaqs[] = [
                'entity_type' => $row['entity_type'],
                'entity_id' => (int) $row['entity_id'],
                'entity_name' => FaqModel::getEntityName($row['entity_type'], (int) $row['entity_id']),
                'id_lang' => (int) $row['id_lang'],
                'question' => $row['question'],
                'answer' => $row['answer'],
                'active' => (bool) $row['active'],
                'position' => (int) $row['position'],
            ];
        }

        $exportData = [
            'exported_from' => 'FS Category FAQ SEO v' . $this->module->version,
            'exported_at' => date('c'),
            'total_faqs' => count($exportFaqs),
            'faqs' => $exportFaqs,
        ];

        $json = json_encode(
            $exportData,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="faqs-export-' . date('Y-m-d') . '.json"');
        header('Content-Length: ' . strlen($json));

        echo $json;
        exit;
    }

    // ---------------------------------------------------------------
    //  FORMULARIO ADD / EDIT
    // ---------------------------------------------------------------

    /**
     * {@inheritdoc}
     */
    public function renderForm(): string
    {
        // JS solo necesario en el formulario (toggle dinámico de entidad)
        $this->addJS($this->module->getPathUri() . 'views/js/admin.js');

        // Asignar el array del formulario
        $this->fields_form = $this->getFieldsForm();

        return parent::renderForm();
    }

    /**
     * Construye el array del formulario para HelperForm.
     *
     * Usa selectores separados por tipo de entidad (categoría/CMS/fabricante)
     * que se muestran/ocultan con JS. No usa 'type' => 'categories' porque
     * no es un tipo estándar de HelperForm en PS 8.2.
     */
    public function getFieldsForm(): array
    {
        $idFaq = (int) Tools::getValue('id_faq');
        $entityType = 'category';
        $entityId = 0;

        // Cargar datos de la FAQ si estamos editando
        if ($idFaq > 0) {
            $faq = new FaqModel($idFaq, $this->context->language->id);
            if (Validate::isLoadedObject($faq)) {
                $entityType = $faq->entity_type;
                $entityId = (int) $faq->entity_id;
            }
        }

        // Construir opciones para los selectores de entidad
        $categoryOptions = $this->buildCategoryOptions();
        $cmsOptions = $this->buildCmsOptions();
        $manufacturerOptions = $this->buildManufacturerOptions();

        return [
            'legend' => [
                'title' => $idFaq
                    ? $this->trans('Editar FAQ', [], 'Modules.Fs_category_faq.Admin')
                    : $this->trans('Nueva FAQ', [], 'Modules.Fs_category_faq.Admin'),
                'icon' => 'icon-question-circle',
            ],
            'input' => [
                    // ── Tipo de entidad ──
                    [
                        'type' => 'select',
                        'label' => $this->trans('Tipo de página', [], 'Modules.Fs_category_faq.Admin'),
                        'name' => 'entity_type',
                        'id' => 'fs-faq-entity-type',
                        'required' => true,
                        'options' => [
                            'query' => [
                                ['id' => 'category', 'name' => $this->trans('Categoría', [], 'Modules.Fs_category_faq.Admin')],
                                ['id' => 'home', 'name' => $this->trans('Inicio', [], 'Modules.Fs_category_faq.Admin')],
                                ['id' => 'cms', 'name' => $this->trans('Página CMS', [], 'Modules.Fs_category_faq.Admin')],
                                ['id' => 'manufacturer', 'name' => $this->trans('Fabricante', [], 'Modules.Fs_category_faq.Admin')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],

                    // ── Selector de categoría (visible si entity_type = category) ──
                    [
                        'type' => 'select',
                        'label' => $this->trans('Categoría', [], 'Modules.Fs_category_faq.Admin'),
                        'name' => 'entity_id_category',
                        'id' => 'fs-faq-entity-category',
                        'options' => [
                            'query' => $categoryOptions,
                            'id' => 'id',
                            'name' => 'name',
                        ],
                        'desc' => $this->trans('Selecciona la categoría donde aparecerá esta FAQ.', [], 'Modules.Fs_category_faq.Admin'),
                    ],

                    // ── Selector de CMS (visible si entity_type = cms) ──
                    [
                        'type' => 'select',
                        'label' => $this->trans('Página CMS', [], 'Modules.Fs_category_faq.Admin'),
                        'name' => 'entity_id_cms',
                        'id' => 'fs-faq-entity-cms',
                        'options' => [
                            'query' => $cmsOptions,
                            'id' => 'id',
                            'name' => 'name',
                        ],
                        'desc' => $this->trans('Selecciona la página CMS donde aparecerá esta FAQ.', [], 'Modules.Fs_category_faq.Admin'),
                    ],

                    // ── Selector de fabricante (visible si entity_type = manufacturer) ──
                    [
                        'type' => 'select',
                        'label' => $this->trans('Fabricante', [], 'Modules.Fs_category_faq.Admin'),
                        'name' => 'entity_id_manufacturer',
                        'id' => 'fs-faq-entity-manufacturer',
                        'options' => [
                            'query' => $manufacturerOptions,
                            'id' => 'id',
                            'name' => 'name',
                        ],
                        'desc' => $this->trans('Selecciona el fabricante donde aparecerá esta FAQ.', [], 'Modules.Fs_category_faq.Admin'),
                    ],

                    // ── Activo ──
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Activo', [], 'Modules.Fs_category_faq.Admin'),
                        'name' => 'active',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->trans('Sí', [], 'Modules.Fs_category_faq.Admin')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->trans('No', [], 'Modules.Fs_category_faq.Admin')],
                        ],
                    ],

                    // ── Pregunta ──
                    [
                        'type' => 'text',
                        'label' => $this->trans('Pregunta', [], 'Modules.Fs_category_faq.Admin'),
                        'name' => 'question',
                        'lang' => true,
                        'required' => true,
                        'maxlength' => 255,
                        'size' => 80,
                        'desc' => $this->trans('Máximo 255 caracteres.', [], 'Modules.Fs_category_faq.Admin'),
                    ],

                    // ── Respuesta ──
                    [
                        'type' => 'textarea',
                        'label' => $this->trans('Respuesta', [], 'Modules.Fs_category_faq.Admin'),
                        'name' => 'answer',
                        'lang' => true,
                        'required' => true,
                        'autoload_rte' => false,
                        'rows' => 8,
                        'cols' => 80,
                        'desc' => $this->trans(
                            'HTML permitido: p, strong, em, ul, ol, li, a, br, table (thead/tbody/tr/td/th), h1–h6, img, span, div, blockquote, pre, code, sub, sup.',
                            [],
                            'Modules.Fs_category_faq.Admin'
                        ),
                    ],

                    // ── Posición ──
                    [
                        'type' => 'text',
                        'label' => $this->trans('Posición', [], 'Modules.Fs_category_faq.Admin'),
                        'name' => 'position',
                        'size' => 5,
                        'maxlength' => 5,
                        'desc' => $this->trans('Se autoasigna si se deja en blanco.', [], 'Modules.Fs_category_faq.Admin'),
                    ],
                ],
            'submit' => [
                'title' => $this->trans('Guardar', [], 'Modules.Fs_category_faq.Admin'),
                'class' => 'btn btn-primary pull-right',
            ],
        ];
    }

    /**
     * Carga los valores del formulario.
     */
    public function getFieldsValue($obj): array
    {
        $values = parent::getFieldsValue($obj);

        if (isset($obj->entity_type)) {
            // Desplegar entity_id en el campo que corresponda según el tipo
            switch ($obj->entity_type) {
                case 'category':
                    $values['entity_id_category'] = (int) $obj->entity_id;
                    break;
                case 'cms':
                    $values['entity_id_cms'] = (int) $obj->entity_id;
                    break;
                case 'manufacturer':
                    $values['entity_id_manufacturer'] = (int) $obj->entity_id;
                    break;
            }
        }

        // Valores por defecto para registros nuevos
        if (empty($obj->id)) {
            $values['active'] = 1;
            $values['entity_type'] = 'category';
        }

        return $values;
    }

    /**
     * Procesa y valida los datos antes de guardar.
     */
    public function postProcess(): void
    {
        // Procesar importación de FAQs desde archivo JSON
        if (Tools::isSubmit('submitImportFaqs')) {
            $this->processImportFaqs();
            // POST-redirect-GET: dejamos que el ciclo normal de PS haga
            // el redirect y persista errores/confirmaciones correctamente
            $this->redirect_after = self::$currentIndex . '&action=import_faqs&token=' . $this->token;
            return;
        }

        if (Tools::isSubmit('submitAdd' . $this->table)) {
            $this->sanitizeSubmittedAnswers();
            $this->resolveEntityFromPost();
            $this->validateFaqSubmission();
        }

        parent::postProcess();
    }

    /**
     * Sanitiza las respuestas enviadas (campos multidioma con sufijo _idLang).
     */
    private function sanitizeSubmittedAnswers(): void
    {
        $languages = Language::getLanguages(false);

        foreach ($languages as $lang) {
            $idLang = (int) $lang['id_lang'];
            $fieldName = 'answer_' . $idLang;
            $raw = Tools::getValue($fieldName);

            if ($raw !== false && $raw !== null) {
                $_POST[$fieldName] = FaqModel::sanitizeAnswer((string) $raw);
            }
        }
    }

    /**
     * Resuelve entity_type + entity_id desde los campos del formulario.
     */
    private function resolveEntityFromPost(): void
    {
        $entityType = (string) Tools::getValue('entity_type', 'category');

        switch ($entityType) {
            case 'category':
                $entityId = (int) Tools::getValue('entity_id_category', 0);
                break;
            case 'cms':
                $entityId = (int) Tools::getValue('entity_id_cms', 0);
                break;
            case 'manufacturer':
                $entityId = (int) Tools::getValue('entity_id_manufacturer', 0);
                break;
            case 'home':
            default:
                $entityId = 0;
                break;
        }

        $_POST['entity_id'] = $entityId;
    }

    /**
     * Validaciones del servidor según especificación.
     *
     * Los campos multidioma de HelperForm se envían con sufijo _idLang
     * (ej: question_1, answer_1), no como arrays question[id_lang].
     */
    private function validateFaqSubmission(): void
    {
        $entityType = (string) Tools::getValue('entity_type', 'category');
        $entityId = (int) Tools::getValue('entity_id', 0);
        $defaultLangId = (int) Configuration::get('PS_LANG_DEFAULT');

        // Validar: categoría/CMS/fabricante debe existir
        if ($entityType !== 'home' && $entityId === 0) {
            $this->errors[] = $this->trans(
                'Debes seleccionar una entidad concreta para este tipo de página.',
                [],
                'Modules.Fs_category_faq.Admin'
            );
        }

        // Validar: pregunta no vacía para el idioma por defecto
        $question = trim((string) Tools::getValue('question_' . $defaultLangId, ''));
        if ($question === '') {
            $this->errors[] = $this->trans(
                'La pregunta no puede estar vacía.',
                [],
                'Modules.Fs_category_faq.Admin'
            );
        }

        // Validar: respuesta no vacía para el idioma por defecto
        $answerRaw = trim((string) Tools::getValue('answer_' . $defaultLangId, ''));
        $answerText = trim(strip_tags($answerRaw));
        if ($answerText === '') {
            $this->errors[] = $this->trans(
                'La respuesta no puede estar vacía.',
                [],
                'Modules.Fs_category_faq.Admin'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function afterAdd($object)
    {
        $this->clearModuleCache();
        parent::afterAdd($object);
    }

    /**
     * {@inheritdoc}
     */
    protected function afterUpdate($object)
    {
        $this->clearModuleCache();
        parent::afterUpdate($object);
    }

    /**
     * {@inheritdoc}
     */
    protected function afterDelete($object, $old_id)
    {
        $this->clearModuleCache();
        parent::afterDelete($object, $old_id);
    }

    /**
     * Limpia la caché del módulo tras cambios.
     */
    private function clearModuleCache(): void
    {
        $module = Module::getInstanceByName('fs_category_faq');
        if ($module && method_exists($module, 'clearFaqCache')) {
            $module->clearFaqCache();
        }
        Tools::clearSmartyCache();
    }

    // ---------------------------------------------------------------
    //  IMPORTACIÓN
    // ---------------------------------------------------------------

    /**
     * Procesa la subida de un archivo JSON con FAQs e inserta cada
     * FAQ en la base de datos tras validar y sanitizar los datos.
     *
     * Los resultados (éxitos y errores) se guardan en
     * $this->confirmations y $this->errors para mostrarse en
     * la página de importación tras la redirección.
     */
    protected function processImportFaqs(): void
    {
        // ── Validar archivo subido ──
        // Comprobaciones separadas para dar un mensaje preciso según el error.

        // Caso 1: el formulario se envió sin archivo
        if (!isset($_FILES['import_file'])) {
            $this->errors[] = $this->trans(
                'No se ha seleccionado ningún archivo. Elige un archivo .json antes de pulsar Importar.',
                [],
                'Modules.Fs_category_faq.Admin'
            );
            return;
        }

        // Caso 2: el archivo se subió pero PHP reporta un error (tamaño, permisos, etc.)
        if ($_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE   => 'El archivo supera el límite de upload_max_filesize del servidor.',
                UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el límite MAX_FILE_SIZE del formulario.',
                UPLOAD_ERR_PARTIAL    => 'El archivo se subió solo parcialmente. Reinténtalo.',
                UPLOAD_ERR_NO_FILE    => 'No se subió ningún archivo (el campo llegó vacío al servidor).',
                UPLOAD_ERR_NO_TMP_DIR => 'Error de configuración del servidor: falta la carpeta temporal de PHP.',
                UPLOAD_ERR_CANT_WRITE => 'Error de permisos del servidor: no se pudo escribir el archivo en disco.',
            ];
            $code = $_FILES['import_file']['error'];
            $msg = $errorMessages[$code] ?? ('Error desconocido al subir el archivo (código ' . $code . ').');

            $this->errors[] = $msg;

            return;
        }

        // ── Validar tamaño máximo (5 MB) ──
        if ($_FILES['import_file']['size'] > 5 * 1024 * 1024) {
            $this->errors[] = $this->trans(
                'El archivo excede el tamaño máximo de 5 MB.',
                [],
                'Modules.Fs_category_faq.Admin'
            );
            return;
        }

        // ── Validar extensión ──
        $extension = strtolower(pathinfo((string) $_FILES['import_file']['name'], PATHINFO_EXTENSION));
        if ($extension !== 'json') {
            $this->errors[] = $this->trans(
                'Solo se aceptan archivos .json.',
                [],
                'Modules.Fs_category_faq.Admin'
            );
            return;
        }

        // ── Leer y decodificar JSON ──
        $content = file_get_contents($_FILES['import_file']['tmp_name']);
        if ($content === false || trim($content) === '') {
            $this->errors[] = $this->trans(
                'El archivo está vacío o no se puede leer.',
                [],
                'Modules.Fs_category_faq.Admin'
            );
            return;
        }

        $data = json_decode($content, false);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errors[] = sprintf(
                $this->trans('El archivo no contiene JSON válido: %s', [], 'Modules.Fs_category_faq.Admin'),
                json_last_error_msg()
            );
            return;
        }

        // ── Validar estructura ──
        if (!isset($data->faqs) || !is_array($data->faqs)) {
            $this->errors[] = $this->trans(
                'Formato incorrecto: el JSON debe contener un array "faqs".',
                [],
                'Modules.Fs_category_faq.Admin'
            );
            return;
        }

        if (empty($data->faqs)) {
            $this->errors[] = $this->trans(
                'El array "faqs" está vacío. No hay nada que importar.',
                [],
                'Modules.Fs_category_faq.Admin'
            );
            return;
        }

        // ── Reemplazar FAQs existentes (opcional) ──
        // Solo se borran las FAQs que pertenecen a las mismas entidades
        // (entity_type + entity_id) que vienen en el archivo, NO toda la tienda.
        $replace = (bool) Tools::getValue('import_replace', false);
        $deletedCount = 0;
        if ($replace) {
            // Recopilar las entidades únicas del archivo
            $targetEntities = [];
            foreach ($data->faqs as $faq) {
                $et = isset($faq->entity_type) ? (string) $faq->entity_type : '';
                $eid = isset($faq->entity_id) ? (int) $faq->entity_id : 0;
                if ($et !== '') {
                    $key = $et . '|' . $eid;
                    $targetEntities[$key] = true;
                }
            }

            if (!empty($targetEntities)) {
                $idShop = (int) $this->context->shop->id;
                $rows = FaqModel::getAllWithTranslations($idShop);
                // Deducir por id_faq: getAllWithTranslations() devuelve una
                // fila por cada traducción, así que una FAQ con 2 idiomas
                // aparece 2 veces. Sin esto, intentaríamos borrar la misma
                // FAQ dos veces.
                $seenFaqIds = [];
                foreach ($rows as $row) {
                    $idFaqRow = (int) $row['id_faq'];
                    $key = $row['entity_type'] . '|' . (int) $row['entity_id'];
                    if (isset($targetEntities[$key]) && !isset($seenFaqIds[$idFaqRow])) {
                        $seenFaqIds[$idFaqRow] = true;
                        $faqToDelete = new FaqModel($idFaqRow);
                        if (Validate::isLoadedObject($faqToDelete) && $faqToDelete->delete()) {
                            ++$deletedCount;
                        }
                    }
                }
            }
        }

        // ── Procesar cada FAQ ──
        $idShop = (int) $this->context->shop->id;
        $defaultLangId = (int) Configuration::get('PS_LANG_DEFAULT');
        $duplicateMode = (string) Tools::getValue('import_duplicates', 'duplicate_add');
        $addedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $unchangedCount = 0;
        $errorCount = 0;
        $errorDetails = [];

        foreach ($data->faqs as $index => $faq) {
            $num = $index + 1;

            try {
                // Validar entity_type
                $entityType = isset($faq->entity_type) ? (string) $faq->entity_type : '';
                if (!in_array($entityType, FaqModel::VALID_ENTITY_TYPES, true)) {
                    throw new \InvalidArgumentException(sprintf(
                        'entity_type "%s" no válido. Valores permitidos: %s',
                        $entityType,
                        implode(', ', FaqModel::VALID_ENTITY_TYPES)
                    ));
                }

                // Validar entity_id
                $entityId = isset($faq->entity_id) ? (int) $faq->entity_id : 0;
                if ($entityType !== 'home' && $entityId <= 0) {
                    throw new \InvalidArgumentException(
                        'entity_id debe ser mayor que 0 para el tipo ' . $entityType
                    );
                }

                // Validar question
                $question = isset($faq->question) ? trim((string) $faq->question) : '';
                if ($question === '') {
                    throw new \InvalidArgumentException('La pregunta no puede estar vacía.');
                }
                if (mb_strlen($question) > 255) {
                    $question = mb_substr($question, 0, 255);
                }

                // Validar answer
                $answerRaw = isset($faq->answer) ? trim((string) $faq->answer) : '';
                $answerText = trim(strip_tags($answerRaw));
                if ($answerText === '') {
                    throw new \InvalidArgumentException('La respuesta no puede estar vacía.');
                }

                // Sanitizar answer (eliminar scripts, iframes, eventos, javascript:)
                $answer = FaqModel::sanitizeAnswer($answerRaw);

                // Determinar active
                $active = isset($faq->active) ? (bool) $faq->active : true;

                // ── Detectar duplicados ──
                $existingId = FaqModel::findByQuestion($entityType, $entityId, $question, $defaultLangId, $idShop);

                if ($existingId > 0 && $duplicateMode === 'duplicate_skip') {
                    // Saltar: no importar la pregunta repetida
                    ++$skippedCount;
                    continue;
                }

                if ($existingId > 0 && $duplicateMode === 'duplicate_update') {
                    // Actualizar la FAQ existente con los nuevos datos
                    $faqModel = new FaqModel($existingId, $defaultLangId);
                    if (!Validate::isLoadedObject($faqModel)) {
                        throw new \RuntimeException('No se pudo cargar la FAQ existente (ID ' . $existingId . ').');
                    }

                    // Comparar respuesta y pregunta actuales con las nuevas
                    // Solo actualizamos si hay cambios reales (evita tocar la BD sin necesidad)
                    $currentAnswer = isset($faqModel->answer[$defaultLangId])
                        ? (string) $faqModel->answer[$defaultLangId]
                        : '';
                    $currentQuestion = isset($faqModel->question[$defaultLangId])
                        ? (string) $faqModel->question[$defaultLangId]
                        : '';

                    $answerChanged = ($answer !== $currentAnswer);
                    $questionChanged = ($question !== $currentQuestion);

                    if (!$answerChanged && !$questionChanged) {
                        // Sin cambios reales: no tocamos la BD
                        ++$unchangedCount;
                        continue;
                    }

                    if ($answerChanged) {
                        $faqModel->answer = [$defaultLangId => $answer];
                    }
                    if ($questionChanged) {
                        $faqModel->question = [$defaultLangId => $question];
                    }
                    $faqModel->active = $active;

                    if (!$faqModel->update()) {
                        throw new \RuntimeException('Error al actualizar la FAQ existente.');
                    }

                    ++$updatedCount;
                    continue;
                }

                // ── Insertar nueva FAQ (modo duplicate_add o no existe) ──
                $faqModel = new FaqModel();
                $faqModel->entity_type = $entityType;
                $faqModel->entity_id = $entityId;
                $faqModel->id_shop = $idShop;
                $faqModel->active = $active;
                $faqModel->question = [];
                $faqModel->answer = [];
                $faqModel->question[$defaultLangId] = $question;
                $faqModel->answer[$defaultLangId] = $answer;

                if (!$faqModel->add()) {
                    throw new \RuntimeException('Error al guardar en la base de datos.');
                }

                ++$addedCount;
            } catch (\Exception $e) {
                ++$errorCount;
                $errorDetails[] = sprintf(
                    $this->trans('FAQ #%d: %s', [], 'Modules.Fs_category_faq.Admin'),
                    $num,
                    $e->getMessage()
                );
            }
        }

        // ── Limpiar caché ──
        $this->clearModuleCache();

        // ── Resumen ──
        $summaryParts = [];
        if ($addedCount > 0) {
            $summaryParts[] = sprintf(
                $this->trans('%d añadidas', [], 'Modules.Fs_category_faq.Admin'),
                $addedCount
            );
        }
        if ($updatedCount > 0) {
            $summaryParts[] = sprintf(
                $this->trans('%d actualizadas', [], 'Modules.Fs_category_faq.Admin'),
                $updatedCount
            );
        }
        if ($skippedCount > 0) {
            $summaryParts[] = sprintf(
                $this->trans('%d saltadas (ya existían)', [], 'Modules.Fs_category_faq.Admin'),
                $skippedCount
            );
        }
        if ($unchangedCount > 0) {
            $summaryParts[] = sprintf(
                $this->trans('%d sin cambios (misma pregunta y respuesta)', [], 'Modules.Fs_category_faq.Admin'),
                $unchangedCount
            );
        }
        if ($errorCount > 0) {
            $summaryParts[] = sprintf(
                $this->trans('%d errores', [], 'Modules.Fs_category_faq.Admin'),
                $errorCount
            );
        }

        $summary = implode(', ', $summaryParts) ?: $this->trans(
            'No se procesó ninguna FAQ.',
            [],
            'Modules.Fs_category_faq.Admin'
        );

        if ($deletedCount > 0) {
            $deleteMsg = sprintf(
                $this->trans('Se eliminaron %d FAQs existentes de las mismas páginas antes de importar.', [], 'Modules.Fs_category_faq.Admin'),
                $deletedCount
            );
            $this->confirmations[] = $deleteMsg;
        }

        $hasSuccess = ($addedCount + $updatedCount + $unchangedCount) > 0;

        if ($hasSuccess && $errorCount === 0) {
            $this->confirmations[] = $summary;
        } elseif ($hasSuccess) {
            $this->confirmations[] = $summary;
            foreach ($errorDetails as $detail) {
                $this->errors[] = $detail;
            }
        } else {
            $this->errors[] = $summary;
            foreach ($errorDetails as $detail) {
                $this->errors[] = $detail;
            }
        }
    }

    // ---------------------------------------------------------------
    //  UTILIDADES
    // ---------------------------------------------------------------

    /**
     * Obtiene la etiqueta legible del tipo de entidad.
     */
    private function getEntityTypeLabel(string $type): string
    {
        $map = [
            'category' => $this->trans('Categoría', [], 'Modules.Fs_category_faq.Admin'),
            'home' => $this->trans('Inicio', [], 'Modules.Fs_category_faq.Admin'),
            'cms' => $this->trans('CMS', [], 'Modules.Fs_category_faq.Admin'),
            'manufacturer' => $this->trans('Fabricante', [], 'Modules.Fs_category_faq.Admin'),
        ];

        return $map[$type] ?? $type;
    }

    /**
     * Construye el array de opciones para el select de categorías.
     *
     * Obtiene la lista plana de Category::getCategories() y la reorganiza
     * en orden jerárquico (padre → sus hijos → siguiente padre → ...)
     * para que el select sea legible.
     */
    private function buildCategoryOptions(): array
    {
        $idLang = (int) $this->context->language->id;
        $categories = Category::getCategories($idLang, false, false);

        if (empty($categories)) {
            return [['id' => 0, 'name' => $this->trans('— Selecciona —', [], 'Modules.Fs_category_faq.Admin')]];
        }

        // Indexar por id_category para acceso rápido
        $byId = [];
        foreach ($categories as &$cat) {
            $cat['children'] = [];
            $byId[(int) $cat['id_category']] = &$cat;
        }
        unset($cat);

        // Construir árbol: cada categoría se cuelga de su padre
        $roots = [];
        foreach ($byId as &$cat) {
            $idParent = (int) $cat['id_parent'];
            if ($idParent > 0 && isset($byId[$idParent])) {
                $byId[$idParent]['children'][] = &$cat;
            } else {
                $roots[] = &$cat;
            }
        }
        unset($cat);

        // Aplanar el árbol recursivamente con indentación
        $options = [['id' => 0, 'name' => $this->trans('— Selecciona —', [], 'Modules.Fs_category_faq.Admin')]];
        $this->flattenCategoryTree($roots, $options, 0);

        return $options;
    }

    /**
     * Aplana recursivamente el árbol de categorías con indentación visual.
     *
     * @param array $tree    Nodos del nivel actual (por referencia para ahorrar memoria)
     * @param array &$options Array de opciones a rellenar
     * @param int   $depth   Profundidad actual (0 = raíz)
     */
    private function flattenCategoryTree(array $tree, array &$options, int $depth): void
    {
        foreach ($tree as $category) {
            $options[] = [
                'id' => (int) $category['id_category'],
                'name' => str_repeat('— ', $depth) . $category['name'],
            ];
            if (!empty($category['children'])) {
                $this->flattenCategoryTree($category['children'], $options, $depth + 1);
            }
        }
    }

    /**
     * Construye el mapa id => nombre para el filtro "Categoría" del listado.
     *
     * Reutiliza buildCategoryOptions() (mismo árbol indentado que el
     * formulario) pero en el formato plano id => nombre que espera el
     * filtro 'type' => 'select' de HelperList, y sin la opción
     * "— Selecciona —" (HelperList ya añade su propio "Todas" en blanco).
     */
    private function buildCategoryFilterOptions(): array
    {
        $options = [];

        foreach ($this->buildCategoryOptions() as $option) {
            $idCategory = (int) $option['id'];
            if ($idCategory > 0) {
                $options[$idCategory] = $option['name'];
            }
        }

        return $options;
    }

    /**
     * Construye el array de opciones para el select de páginas CMS.
     */
    private function buildCmsOptions(): array
    {
        $idLang = (int) $this->context->language->id;
        $cmsPages = CMS::getCMSPages($idLang, null, true, $this->context->shop->id);
        $options = [['id' => 0, 'name' => $this->trans('— Selecciona —', [], 'Modules.Fs_category_faq.Admin')]];

        foreach ($cmsPages as $page) {
            $options[] = [
                'id' => (int) $page['id_cms'],
                'name' => $page['meta_title'] ?: 'CMS #' . $page['id_cms'],
            ];
        }

        return $options;
    }

    /**
     * Construye el array de opciones para el select de fabricantes.
     */
    private function buildManufacturerOptions(): array
    {
        $manufacturers = Manufacturer::getManufacturers(false, $this->context->language->id);
        $options = [['id' => 0, 'name' => $this->trans('— Selecciona —', [], 'Modules.Fs_category_faq.Admin')]];

        foreach ($manufacturers as $manufacturer) {
            $options[] = [
                'id' => (int) $manufacturer['id_manufacturer'],
                'name' => $manufacturer['name'],
            ];
        }

        return $options;
    }
}
