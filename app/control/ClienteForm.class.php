<?php
/**
 * ClienteForm Form
 * @author  <your name here>
 */
class ClienteForm extends TPage
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
        $this->form = new BootstrapFormBuilder('form_Cliente');
        $this->form->setFormTitle('Cliente');
        

        // create the form fields
        $cliente_id = new THidden('cliente_id');
        $nome = new TEntry('nome');
        $email = new TEntry('email');
        $telefone = new TEntry('telefone');
        $cpf = new TEntry('cpf');
        $data_nascimento = new TDate('data_nascimento');


        // add the fields
        $this->form->addFields( [ new TLabel('Nome :') ], [ $nome ] );
        $this->form->addFields( [ new TLabel('Email :') ], [ $email ] );
        $this->form->addFields( [ new TLabel('Telefone :') ], [ $telefone ] );
        $this->form->addFields( [ new TLabel('CPF :') ], [ $cpf ] );
        $this->form->addFields( [ new TLabel('Data Nascimento :') ], [ $data_nascimento ] );



        // set sizes
        $cliente_id->setSize('50%');
        $nome->setSize('50%');
        $email->setSize('50%');
        $telefone->setSize('50%');
        $cpf->setSize('50%');
        $data_nascimento->setSize('50%');



        if (!empty($cliente_id))
        {
            $cliente_id->setEditable(FALSE);
        }

        if (!empty($cpf))
        {
            $cpf->setEditable(FALSE);
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
            
            $object = new Cliente;  // create an empty object
            $object->fromArray( (array) $data); // load the object with data
            $object->store(); // save the object
            
            // get the generated cliente_id
            $data->cliente_id = $object->cliente_id;
            
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
                $object = new Cliente($key); // instantiates the Active Record
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
