<?php
namespace UNL\VisitorChat\Conversation;

class View
{
    public $conversation_id = false;
    
    public $conversation = false;
    
    public $messages = array();
    
    public $request_id = 0;
    
    public $invitations = false;
    
    public $clientInfo = false;
    
    public $sendHTML = false;
    
    public $operators = array();
    
    function __construct($options = array())
    {
        //Always require that someone is logged in
        \UNL\VisitorChat\Controller::requireClientLogin();
        
        //Get the current user.
        $user = \UNL\VisitorChat\User\Service::getCurrentUser();
        
        //Get and set the conversation for viewing.
        if (isset($options['conversation_id']) && $user->type == 'operator') {
            $this->conversation_id = $options['conversation_id'];
            
            //get the latest conversation.
            if (!$this->conversation = \UNL\VisitorChat\Conversation\Record::getByID($this->conversation_id)) {
                throw new \Exception("No conversation was found!", 500);
            }

            if (isset($options['clientInfo'])) {
                $this->clientInfo = \UNL\VisitorChat\Conversation\ClientInfo::getFromConversationRecord($this->conversation);
            }
            
            $this->invitations = $this->conversation->getInvitations();
        } else {
            //Just get the latest conversation.
            if (!$this->conversation = $user->getConversation()) {
                throw new \Exception("Could not find a conversation", 500);
            }
            $this->conversation_id = $this->conversation->id;
        }
        
        //Handle assignments for the conversation.
        $invitationService = new \UNL\VisitorChat\Invitation\Service();
        $this->conversation = $invitationService->handleInvitations($this->conversation);
        
        foreach ($this->conversation->getAcceptedAssignments() as $assignment) {
            if ($operator = $assignment->getUser()) {
                $this->operators[] = array(
                    'name' => $operator->name,
                    'assignment' => $assignment->id,
                    'id' => $operator->id,
                    'is_typing' => $assignment->is_typing
                );
            }
        }

        if (isset($options['last'])) {
            $this->request_id = $options['last'];
        }
        
        $this->messages = \UNL\VisitorChat\Message\RecordList::getMessagesAfterIDForConversation($this->conversation_id, $this->request_id);
        
        //Only send html output if we have to (to reduce size of response).
        if ($this->request_id == 0) {
            $this->sendHTML = true;
        }
        
        //save the last viewed time to the session (for operators).
        $_SESSION['last_viewed'][$this->conversation->id] = \UNL\VisitorChat\Controller::epochToDateTime();
    }
}
