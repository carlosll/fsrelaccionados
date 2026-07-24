<?php
/**
 * FS Category FAQ SEO — Clase ObjectModel para FAQs
 *
 * Gestiona la entidad FAQ con soporte multidioma, validación de datos
 * y métodos estáticos de consulta para el front office.
 */

declare(strict_types=1);

namespace FSCategoryFaq;

use Db;
use DbQuery;
use ObjectModel;
use Tools;
use Validate;

if (!defined('_PS_VERSION_')) {
    exit;
}

class FsCategoryFaq extends ObjectModel
{
    /** @var int ID de la FAQ */
    public $id_faq;

    /** @var string Tipo de entidad: category, home, cms, manufacturer */
    public $entity_type = 'category';

    /** @var int ID de la entidad asociada (0 para home) */
    public $entity_id = 0;

    /** @var int ID de la tienda */
    public $id_shop = 1;

    /** @var bool Estado activo/inactivo */
    public $active = true;

    /** @var int Posición para ordenación */
    public $position = 0;

    /** @var string Fecha de creación */
    public $date_add;

    /** @var string Fecha de actualización */
    public $date_upd;

    // Campos multidioma
    /** @var string|string[] Pregunta (multidioma) */
    public $question;

    /** @var string|string[] Respuesta con HTML básico (multidioma) */
    public $answer;

    /**
     * Tipos de entidad válidos.
     */
    public const VALID_ENTITY_TYPES = [
        'category',
        'home',
        'cms',
        'manufacturer',
    ];

    /**
     * {@inheritdoc}
     */
    public static $definition = [
        'table' => 'fs_category_faq',
        'primary' => 'id_faq',
        'multilang' => true,
        'fields' => [
            // Campos principales
            'entity_type' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'required' => true,
                'size' => 32,
            ],
            'entity_id' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true,
            ],
            'id_shop' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true,
            ],
            'active' => [
                'type' => self::TYPE_BOOL,
                'validate' => 'isBool',
            ],
            'position' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
            ],
            'date_add' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
            ],
            'date_upd' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
            ],

            // Campos multidioma
            'question' => [
                'type' => self::TYPE_STRING,
                'lang' => true,
                'validate' => 'isCleanHtml',
                'required' => true,
                'size' => 255,
            ],
            'answer' => [
                'type' => self::TYPE_HTML,
                'lang' => true,
                'validate' => 'isCleanHtml',
                'required' => true,
            ],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct(?int $id = null, ?int $idLang = null, ?int $idShop = null)
    {
        parent::__construct($id, $idLang, $idShop);

        if ($idShop === null && $this->id_shop === 0) {
            $this->id_shop = (int) \Context::getContext()->shop->id;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add($autoDate = true, $nullValues = false): bool
    {
        // Asignar posición automática si no tiene
        if (empty($this->position)) {
            $this->position = $this->getNextPosition();
        }

        // Validar entity_type
        if (!in_array($this->entity_type, self::VALID_ENTITY_TYPES, true)) {
            $this->entity_type = 'category';
        }

        // home siempre tiene entity_id = 0
        if ($this->entity_type === 'home') {
            $this->entity_id = 0;
        }

        return (bool) parent::add($autoDate, $nullValues);
    }

    /**
     * {@inheritdoc}
     */
    public function update($nullValues = false): bool
    {
        // home siempre tiene entity_id = 0
        if ($this->entity_type === 'home') {
            $this->entity_id = 0;
        }

        return (bool) parent::update($nullValues);
    }

    /**
     * Reordena la FAQ al arrastrar una fila en el listado del back office.
     *
     * PrestaShop llama a $object->updatePosition($way, $position) desde
     * AdminController::processPosition() (ver .ref/prestashop-8.2/AdminController.php).
     * ObjectModel no trae una implementación genérica: cada entidad con
     * posición debe definir la suya (ej. CMS::updatePosition() del core,
     * que agrupa por id_cms_category). Aquí agrupamos por entity_type +
     * entity_id + id_shop, igual que ya hace getNextPosition(), para que
     * arrastrar una FAQ no mezcle el orden con el de otra categoría/página.
     *
     * @param int $way      1 = mover hacia abajo, 0 = mover hacia arriba
     * @param int $position Posición destino
     */
    public function updatePosition(int $way, int $position): bool
    {
        $table = _DB_PREFIX_ . self::$definition['table'];

        $rows = Db::getInstance()->executeS(
            'SELECT `id_faq`, `position`
            FROM `' . $table . '`
            WHERE `entity_type` = \'' . pSQL($this->entity_type) . '\'
            AND `entity_id` = ' . (int) $this->entity_id . '
            AND `id_shop` = ' . (int) $this->id_shop . '
            ORDER BY `position` ASC'
        );

        if (!$rows) {
            return false;
        }

        $movedFaq = null;
        foreach ($rows as $row) {
            if ((int) $row['id_faq'] === (int) $this->id) {
                $movedFaq = $row;
                break;
            }
        }

        if ($movedFaq === null) {
            return false;
        }

        $scopeWhere = ' AND `entity_type` = \'' . pSQL($this->entity_type) . '\'
            AND `entity_id` = ' . (int) $this->entity_id . '
            AND `id_shop` = ' . (int) $this->id_shop;

        return Db::getInstance()->execute(
            'UPDATE `' . $table . '`
            SET `position` = `position` ' . ($way ? '- 1' : '+ 1') . '
            WHERE `position` '
            . ($way
                ? '> ' . (int) $movedFaq['position'] . ' AND `position` <= ' . $position
                : '< ' . (int) $movedFaq['position'] . ' AND `position` >= ' . $position)
            . $scopeWhere
        ) && Db::getInstance()->execute(
            'UPDATE `' . $table . '`
            SET `position` = ' . $position . '
            WHERE `id_faq` = ' . (int) $movedFaq['id_faq']
            . $scopeWhere
        );
    }

    /**
     * Obtiene la siguiente posición disponible para la entidad/tienda.
     */
    private function getNextPosition(): int
    {
        $sql = new DbQuery();
        $sql->select('MAX(position)');
        $sql->from(self::$definition['table']);
        $sql->where('entity_type = \'' . pSQL($this->entity_type) . '\'');
        $sql->where('entity_id = ' . (int) $this->entity_id);
        $sql->where('id_shop = ' . (int) $this->id_shop);

        $max = (int) Db::getInstance()->getValue($sql);

        return $max + 1;
    }

    // ---------------------------------------------------------------
    //  MÉTODOS ESTÁTICOS DE CONSULTA
    // ---------------------------------------------------------------

    /**
     * Obtiene las FAQs activas para una entidad, tienda e idioma.
     *
     * @param string $entityType Tipo de entidad
     * @param int    $entityId   ID de la entidad
     * @param int    $idLang     ID del idioma
     * @param int    $idShop     ID de la tienda
     * @param int    $limit      Número máximo de resultados
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getByEntity(
        string $entityType,
        int $entityId,
        int $idLang,
        int $idShop,
        int $limit = 10
    ): array {
        $sql = new DbQuery();
        $sql->select('f.id_faq, f.entity_type, f.entity_id, f.position');
        $sql->select('fl.question, fl.answer');
        $sql->from(self::$definition['table'], 'f');
        $sql->innerJoin(
            self::$definition['table'] . '_lang',
            'fl',
            'f.id_faq = fl.id_faq AND fl.id_lang = ' . $idLang
        );
        $sql->where('f.entity_type = \'' . pSQL($entityType) . '\'');
        $sql->where('f.entity_id = ' . $entityId);
        $sql->where('f.id_shop = ' . $idShop);
        $sql->where('f.active = 1');
        $sql->where('fl.question IS NOT NULL AND fl.question != \'\'');
        $sql->where('fl.answer IS NOT NULL AND fl.answer != \'\'');
        $sql->orderBy('f.position ASC, f.id_faq ASC');
        $sql->limit($limit);

        $result = Db::getInstance()->executeS($sql);

        return is_array($result) ? $result : [];
    }

    /**
     * Obtiene todas las FAQs para el back office (listado con filtros).
     *
     * @param int|null    $idLang     ID del idioma (null = todos)
     * @param int|null    $idShop     ID de la tienda
     * @param string|null $entityType Filtrar por tipo de entidad
     * @param int|null    $entityId   Filtrar por ID de entidad
     * @param int         $limit      Límite
     * @param int         $offset     Offset
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getAll(
        ?int $idLang = null,
        ?int $idShop = null,
        ?string $entityType = null,
        ?int $entityId = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $sql = new DbQuery();
        $sql->select('f.*, fl.question, fl.answer, fl.id_lang');
        $sql->from(self::$definition['table'], 'f');
        $sql->leftJoin(
            self::$definition['table'] . '_lang',
            'fl',
            'f.id_faq = fl.id_faq' . ($idLang ? ' AND fl.id_lang = ' . $idLang : '')
        );

        if ($idShop !== null) {
            $sql->where('f.id_shop = ' . $idShop);
        }

        if ($entityType !== null && $entityType !== '') {
            $sql->where('f.entity_type = \'' . pSQL($entityType) . '\'');
        }

        if ($entityId !== null && $entityId > 0) {
            $sql->where('f.entity_id = ' . $entityId);
        } elseif ($entityType === 'home') {
            $sql->where('f.entity_id = 0');
        }

        $sql->orderBy('f.entity_type ASC, f.entity_id ASC, f.position ASC, f.id_faq ASC');
        $sql->limit($limit, $offset);

        $result = Db::getInstance()->executeS($sql);

        return is_array($result) ? $result : [];
    }

    /**
     * Cuenta el total de FAQs para la paginación del back office.
     *
     * @param int|null    $idShop     ID de la tienda
     * @param string|null $entityType Filtrar por tipo de entidad
     * @param int|null    $entityId   Filtrar por ID de entidad
     *
     * @return int
     */
    public static function getTotal(
        ?int $idShop = null,
        ?string $entityType = null,
        ?int $entityId = null
    ): int {
        $sql = new DbQuery();
        $sql->select('COUNT(*)');
        $sql->from(self::$definition['table'], 'f');

        if ($idShop !== null) {
            $sql->where('f.id_shop = ' . $idShop);
        }

        if ($entityType !== null && $entityType !== '') {
            $sql->where('f.entity_type = \'' . pSQL($entityType) . '\'');
        }

        if ($entityId !== null && $entityId > 0) {
            $sql->where('f.entity_id = ' . $entityId);
        } elseif ($entityType === 'home') {
            $sql->where('f.entity_id = 0');
        }

        return (int) Db::getInstance()->getValue($sql);
    }

    /**
     * Obtiene el nombre legible de una entidad (para el back office).
     *
     * @param string $entityType
     * @param int    $entityId
     *
     * @return string
     */
    public static function getEntityName(string $entityType, int $entityId): string
    {
        switch ($entityType) {
            case 'category':
                if ($entityId > 0) {
                    $category = new \Category($entityId, (int) \Context::getContext()->language->id);
                    if (\Validate::isLoadedObject($category)) {
                        return $category->name;
                    }
                }
                return '—';

            case 'home':
                return 'Inicio';

            case 'cms':
                if ($entityId > 0) {
                    $cms = new \CMS($entityId, (int) \Context::getContext()->language->id);
                    if (\Validate::isLoadedObject($cms)) {
                        return $cms->meta_title ?: 'CMS #' . $entityId;
                    }
                }
                return '—';

            case 'manufacturer':
                if ($entityId > 0) {
                    $manufacturer = new \Manufacturer($entityId, (int) \Context::getContext()->language->id);
                    if (\Validate::isLoadedObject($manufacturer)) {
                        return $manufacturer->name;
                    }
                }
                return '—';

            default:
                return $entityType . ' #' . $entityId;
        }
    }

    /**
     * Sanitiza el contenido de la respuesta antes de guardar.
     * Elimina scripts, eventos inline, iframes y HTML peligroso.
     *
     * @param string $html
     *
     * @return string
     */
    public static function sanitizeAnswer(string $html): string
    {
        // Eliminar etiquetas <script> y su contenido
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);

        // Eliminar atributos de eventos (onclick, onload, onerror, etc.)
        $html = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        $html = preg_replace('/\s+on\w+\s*=\s*[^\s>]+/i', '', $html);

        // Eliminar iframes
        $html = preg_replace('/<iframe\b[^>]*>(.*?)<\/iframe>/is', '', $html);

        // Eliminar javascript: / data: en href (XSS)
        $html = preg_replace('/href\s*=\s*["\'](?:javascript|data)\s*:[^"\']*["\']/i', 'href="#"', $html);
        // Eliminar javascript: / data: en src (XSS en <img>, <iframe>, etc.)
        $html = preg_replace('/src\s*=\s*["\'](?:javascript|data)\s*:[^"\']*["\']/i', 'src="#"', $html);

        // Etiquetas permitidas (párrafos, formato, listas, enlaces, tablas, encabezados, etc.)
        $allowedTags = '<p><strong><em><ul><ol><li><a><br>'
            . '<table><thead><tbody><tfoot><tr><th><td><caption><colgroup><col>'
            . '<b><i><u><s><hr><h1><h2><h3><h4><h5><h6>'
            . '<img><span><div><blockquote><pre><code><sub><sup>';

        return strip_tags($html, $allowedTags);
    }

    /**
     * Busca una FAQ existente por su pregunta en una entidad concreta.
     *
     * Útil para evitar duplicados al importar: si ya hay una FAQ con la
     * misma pregunta en la misma página, se puede decidir si saltarla o
     * actualizarla en vez de crear un duplicado.
     *
     * @param string $entityType Tipo de entidad (category, home, cms, manufacturer)
     * @param int    $entityId   ID de la entidad
     * @param string $question   Texto exacto de la pregunta
     * @param int    $idLang     ID del idioma
     * @param int    $idShop     ID de la tienda
     *
     * @return int ID de la FAQ encontrada, o 0 si no existe
     */
    public static function findByQuestion(
        string $entityType,
        int $entityId,
        string $question,
        int $idLang,
        int $idShop
    ): int {
        $sql = new DbQuery();
        $sql->select('f.id_faq');
        $sql->from(self::$definition['table'], 'f');
        $sql->innerJoin(
            self::$definition['table'] . '_lang',
            'fl',
            'f.id_faq = fl.id_faq AND fl.id_lang = ' . $idLang
        );
        $sql->where('f.entity_type = \'' . pSQL($entityType) . '\'');
        $sql->where('f.entity_id = ' . $entityId);
        $sql->where('f.id_shop = ' . $idShop);
        $sql->where('fl.question = \'' . pSQL($question) . '\'');

        $result = Db::getInstance()->getValue($sql);

        return $result ? (int) $result : 0;
    }

    /**
     * Obtiene todas las FAQs con sus traducciones para exportación.
     *
     * @param int      $idShop         ID de la tienda
     * @param int|null $languageFilter Filtrar por idioma (null = todos los idiomas)
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getAllWithTranslations(int $idShop, ?int $languageFilter = null): array
    {
        $sql = new DbQuery();
        $sql->select('f.*, fl.id_lang, fl.question, fl.answer');
        $sql->from(self::$definition['table'], 'f');
        $sql->leftJoin(
            self::$definition['table'] . '_lang',
            'fl',
            'f.id_faq = fl.id_faq'
            . ($languageFilter ? ' AND fl.id_lang = ' . $languageFilter : '')
        );
        $sql->where('f.id_shop = ' . $idShop);
        $sql->orderBy('f.entity_type ASC, f.entity_id ASC, f.position ASC, f.id_faq ASC, fl.id_lang ASC');

        $result = Db::getInstance()->executeS($sql);

        return is_array($result) ? $result : [];
    }
}
