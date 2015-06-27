<?php
/**
 * Created by PhpStorm.
 * User: André
 * Date: 26/06/2015
 * Time: 23:22
 */

class DataManager {

    private $_dataFields = array();

    //Armazena os campos pra cada tipo de dado, e tambem se o campo é obrigatorio (true)
    private $_validFields = array(

        'User' => array(
            'idUser'            => false,
            'userName'         => true,
            'session'              => false
        ),

        'Picture' => array(
            'idPicture'            => false,
            'idUser'         => true,
            'path'         => true,
            'latitude'         => false,
            'longitude'         => false,
            'date'         => false,
            'address'         => false,
            'city'         => false,
            'country'         => false,
            'isFolder'              => false
        )

    );

    private $db;
    private $log;
    private $type;

    /**
     * constructor
     *
     * @param string $type tipo de datamanager a ser incializado
     *
     * inicializa o objeto de log e de bd
     * inicializa o vetor de dados nulo de acordo com o tipo
     */
    function __construct($type)
    {
        $this->db = MysqliDb::getInstance();
        $this->log = Log::getInstance();

        //tipo de dado valido pra iniciar
        if (array_key_exists($type, $this->_validFields))
        {
            $this->type = $type;

            foreach($this->_validFields[$this->type] as $key => $value)
                $this->setField($key, null);
        }
    }

    /**
     * setField
     *
     * set um campo da instancia atual do tipo
     *
     * @param string $fieldName nome do campo
     * @param string $fieldValue valor do campo
     * @return bool retorna true se conseguiu setar o campo, falso caso contrario
     */
    public function setField($fieldName, $fieldValue)
    {
        if (!array_key_exists($fieldName, $this->_validFields[$this->type]))
            return false;

        $this->_dataFields[$fieldName] = $fieldValue;

        return true;
    }

    /**
     * getField
     *
     * retorna o campo da instancia atual do tipo
     *
     * @param string $fieldName nome do campo a retornar, se NULL retorna vetor com todos os campos
     * @return array|string|bool retorna false se nao possui campo, string/array caso contrario
     */
    public function getField($fieldName = null)
    {
        if ($fieldName == null)
        {
            $fields = array();

            foreach($this->_validFields[$this->type] as $key => $value)
                $fields[$key] = $this->getField($key);

            return $fields;
        }

        if (!array_key_exists($fieldName, $this->_validFields[$this->type]))
            return false;

        return $this->_dataFields[$fieldName];
    }

    /**
     * load
     *
     * a partir do ID identificado, carrega os dados na instancia atual
     *
     * @return bool (true quando carregou, false quando nao encontrou)
     */
    public function load($id = null)
    {
        if ($id == null)
        {
            foreach($this->_validFields[$this->type] as $key => $value)
            {
                if ($value == true && $this->getField($key) == null)
                {
                    $this->log->pushLog($this, 500, 'Campo obrigatorio faltando: ' . $key);
                    return false;
                }

                if ($value == true)
                    $this->db->where($key, $this->getField($key));
            }
        }
        else
        {
            $this->db->where('id' . $this->type, $id);
        }

        $data = $this->db->getOne(strtolower($this->type));

        if ($data == null)
            return false;
        else
        {
            foreach ($data as $key => $value)
                $this->setField($key, $value);

            return true;
        }
    }

    /**
     * save
     *
     * a partir das informaçoes da instancia, salva (cria ou atualiza) no BD
     *
     * @return bool (true quando salvou, false quando nao deu certo - provavelmente faltou campo obrigatorio)
     */
    public function save($requiredFieldsOnly = true)
    {
        /**
         * Verificar primeiro se os campos obrigatorios estão setados
         */
        $requiredFields = array();
        foreach($this->_validFields[$this->type] as $key => $value)
        {
            if ($value == true && $this->getField($key) == null)
            {
                $this->log->pushLog($this, 500, 'Campo obrigatorio faltando: ' . $key, true);
                return false;
            }

            if ($value == true)
                array_push($requiredFields, $this->getField($key));
        }
        $requiredFields = implode(" + ", $requiredFields);

        /**
         * Verificar se já existe essa entrada no BD
         */
        //Se a ID estiver setada, buscar por ela
        if ($this->getField('id' . $this->type) != null)
        {
            $this->db->where('id' . $this->type, $this->getField('id' . $this->type));
        }
        //Se nao procurar pelos campos obrigatorios
        else
        {
            if ($requiredFieldsOnly)
            {
                //Procura so pelos campos obrigatorios
                foreach($this->_validFields[$this->type] as $key => $value)
                    if ($value == true)
                        $this->db->where($key, $this->getField($key));
            }
            else
            {
                //Procura por todos setados
                foreach($this->getField() as $key => $value)
                    if ($value == true)
                        $this->db->where($key, $value);
            }
        }

        $data = $this->db->getOne(strtolower($this->type));

        /**
         * Preparando campos pra inserir/atualizar no BD
         */
        $fields = array();

        foreach ($this->getField(null) as $key => $value)
            if ($key != 'id'.$this->type && $value !== null)
                $fields[$key] = $value;


        /**
         * Se não existe, vamos criar
         */
        if ($data == null)
        {
            $insert = $this->db->insert(strtolower($this->type), $fields);

            if ($insert)
            {
                $this->log->pushLog($this, 200, $this->type . ' criado no BD (' . $requiredFields . ')');
                $this->setField('id' . $this->type, $insert);
            }
            else
                $this->log->pushLog($this, 500, 'Erro criando ' . $this->type . ': ' . $this->db->getLastError(), true);
        }
        /**
         * Se já existe, vamos atualizar com os dados da instancia
         */
        else
        {
            $this->db->where('id' . $this->type, $data['id' . $this->type]);

            if ($this->db->update(strtolower($this->type), $fields))
            {
                $this->log->pushLog($this, 200, $this->type . ' atualizado no BD (' . $requiredFields . ')');
                $this->setField('id' . $this->type, $data['id' . $this->type]);
            }
            else
                $this->log->pushLog($this, 500, 'Erro atualizando ' . $this->type . ': ' . $this->db->getLastError(), true);
        }

        /**
         * Recarregando tudo do BD pra unir dados que ja existiam no BD com os novos inseridos/atualizados
         */
        $this->load($this->getField('id' . $this->type));

        return true;
    }




}