<?php
/**
 * CampanhaForm Form
 * @author  <your name here>
 */
class CampanhaForm extends TPage
{
    protected $form; // form
    
    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();
        
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_Campanha');
        $this->form->setFormTitle('Cadastro de Campanha');
        

        // create the form fields
        $campanha_id = new THidden('campanha_id');
        $nome = new TEntry('nome');
        $descricao = new TText('descricao');
        $data_inicio = new TDate('data_inicio');
        $data_fim = new TDate('data_fim');
        $ativa = new TEntry('ativa');


        // add the fields
        //$this->form->addFields( [ new TLabel('Campanha Id') ], [ $campanha_id ] );
        $this->form->addFields( [ new TLabel('NOME : ') ], [ $nome ] );
        $this->form->addFields( [ new TLabel('DESCRIÇÃO :') ], [ $descricao ] );
        $this->form->addFields( [ new TLabel('DATA INICIO :') ], [ $data_inicio ] );
        $this->form->addFields( [ new TLabel('DATA FIM :') ], [ $data_fim ] );
        $this->form->addFields( [ new TLabel('ATIVA :') ], [ $ativa ] );



        // set sizes
        //$campanha_id->setSize('100%');
        $nome->setSize('50%');
        $descricao->setSize('50%');
        $data_inicio->setSize('50%');
        $data_fim->setSize('50%');
        $ativa->setSize('50%');



        if (!empty($campanha_id))
        {
            $campanha_id->setEditable(FALSE);
        }
        
        /** samples
         $fieldX->addValidation( 'Field X', new TRequiredValidator ); // add validation
         $fieldX->setSize( '100%' ); // set size
         **/
         
        // create the form actions
        $btn = $this->form->addAction(_t('Save'), new TAction([$this, 'onSave']), 'fa:save');
        $btn->class = 'btn btn-sm btn-primary';
        $this->form->addActionLink(_t('New'),  new TAction([$this, 'onEdit']), 'fa:eraser red');
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        
        parent::add($container);
    }

    /**
     * Save form data
     * @param $param Request
     */
    public function onSave( $param )
    {
        try
        {
            TTransaction::open('communication'); // open a transaction
            
            
            // Enable Debug logger for SQL operations inside the transaction
            TTransaction::setLogger(new TLoggerSTD); // standard output
            TTransaction::setLogger(new TLoggerTXT('log.txt')); // file
            
            
            $this->form->validate(); // validate form data
            $data = $this->form->getData(); // get form data as array
            
            $object = new Campanha;  // create an empty object
            $object->fromArray( (array) $data); // load the object with data
            $object->store(); // save the object
            
            // get the generated campanha_id
            $data->campanha_id = $object->campanha_id;
            
            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction
            
            new TMessage('info', AdiantiCoreTranslator::translate('Record saved'));
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }
    
    /**
     * Clear form data
     * @param $param Request
     */
    public function onClear( $param )
    {
        $this->form->clear(TRUE);
    }
    
    /**
     * Load object to form data
     * @param $param Request
     */
    public function onEdit( $param )
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open('communication'); // open a transaction
                $object = new Campanha($key); // instantiates the Active Record
                $this->form->setData($object); // fill the form
                TTransaction::close(); // close the transaction
            }
            else
            {
                $this->form->clear(TRUE);
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }
}
