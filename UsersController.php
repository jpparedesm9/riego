<?php
namespace AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use SiteConfigurationBundle\Entity\User;
use AdminBundle\Entity\Cobro;
use AdminBundle\Entity\Area;
use AdminBundle\Entity\Multa;
use AdminBundle\Entity\Reunion;
use AdminBundle\Entity\Asistente;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;


class UsersController extends Controller
{
    public function __construct(EntityManagerInterface $em,ContainerInterface $container)  {
        $this->em = $em;
        $this->container=$container;
        $this->loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        $this->company=$this->loggedUser->getCompany();

    }
    /***********Reportes****************/
    public function turnosRiegoAction(){
      $usuarios=$this->em->createQueryBuilder()
      ->select('o')
      ->from('SiteConfigurationBundle\Entity\User','o')
      ->where('o.name!=:todos')
      ->setParameter('todos','Todos')
      ->orderBy('o.lastname','DESC')
      ->getQuery()
      ->getResult();
      return $this->render('AdminBundle:Reportes:turnos_riego.html.twig',array("usuarios"=>$usuarios));
    }

    public function morososAction($anio,$mes){
      $multas=$this->em->createQueryBuilder()
      ->select('o')
      ->from('AdminBundle\Entity\Multa','o')
      ->where('o.mes=:mes and o.anio=:anio')
      ->setParameter('mes',$mes)
      ->setParameter('anio',$anio)
      ->getQuery()
      ->getResult();
      return $this->render('AdminBundle:Reportes:morosos.html.twig',array("multas"=>$multas));
    }
    public function sociosAlDiaAction($anio,$mes){
      $cobros = $this->em->createQueryBuilder()
      ->select('o')
      ->from('AdminBundle\Entity\Cobro','o')
      ->where('o.mes=:mes and o.anio=:anio')
      ->setParameter('mes',$mes)
      ->setParameter('anio',$anio)
      ->orderBy('o.id','DESC')
      ->getQuery()
      ->getResult();
      return $this->render('AdminBundle:Reportes:socios_al_dia.html.twig',array("cobros"=>$cobros));
    }
    public function recaudacionAction($anio,$mes){
      $cobros = $this->em->createQueryBuilder()
              ->select('o')
              ->from('AdminBundle\Entity\Cobro','o')
              ->where('o.mes=:mes and o.anio=:anio')
              ->setParameter('mes',$mes)
              ->setParameter('anio',$anio)
              ->orderBy('o.id','DESC')
              ->getQuery()
              ->getResult();
      return $this->render('AdminBundle:Reportes:recaudacion.html.twig',["cobros"=>$cobros]);
    }
    public function deudasPorUsuarioAction($anio,$mes){
      $asistentes = $this->em->createQueryBuilder()
      ->select('p.id,p.name, p.lastname, count(o.id) as faltas')
      ->from('AdminBundle\Entity\Asistente','o')
      ->innerJoin('o.usuario','p')
      ->where('o.cancelado=false')
      ->andwhere('o.presente=false')
      ->groupBy('p.id')
      ->having('count(o.id)>=3')
      ->getQuery()
      ->getResult();

      $multas=$this->em->createQueryBuilder()
      ->select('o')
      ->from('AdminBundle\Entity\Multa','o')
      ->getQuery()
      ->getResult();

      return $this->render('AdminBundle:Reportes:deudas_por_usuario.html.twig',array("asistentes"=>$asistentes,"multas"=>$multas));
    }
    public function deudasPorUsuarioIndividualAction($usuario){
      $asistentes = $this->em->createQueryBuilder()
      ->select('p.id,p.name, p.lastname, count(o.id) as faltas')
      ->from('AdminBundle\Entity\Asistente','o')
      ->innerJoin('o.usuario','p')
      ->where('o.cancelado=false')
      ->andwhere('o.presente=false')
      ->andWhere('p=:user_id')
      ->setParameter('user_id',$usuario)
      ->groupBy('p.id')
      ->having('count(o.id)>=3')
      ->getQuery()
      ->getResult();

      $multas=$this->em->createQueryBuilder()
      ->select('o')
      ->from('AdminBundle\Entity\Multa','o')
      ->where('o.user=:usuario')
      ->setParameter('usuario',$usuario)
      ->getQuery()
      ->getResult();

      $usuarios=$this->em->createQueryBuilder()
      ->select('o')
      ->from('SiteConfigurationBundle\Entity\User','o')
      ->where('o.name!=:todos')
      ->setParameter('todos','Todos')
      ->orderBy('o.lastname','DESC')
      ->getQuery()
      ->getResult();

      return $this->render('AdminBundle:Reportes:deudas_por_usuario_individual.html.twig',array("asistentes"=>$asistentes,"multas"=>$multas,"usuarios"=>$usuarios));
    }
    public function asistenciaReunionAction($anio,$mes){
      $asistentes = $this->em->createQueryBuilder()
      ->select('o')
      ->from('AdminBundle\Entity\Asistente','o')
      ->innerJoin('o.reunion','r')
      ->where('MONTH(r.fecha)=:mes and YEAR(r.fecha)=:anio and r.tipo=:tipo')
      ->setParameter('mes',$mes)
      ->setParameter('anio',$anio)
      ->setParameter('tipo',"reunion")
      ->orderBy('o.id','DESC')
      ->getQuery()
      ->getResult();
      return $this->render('AdminBundle:Reportes:asistencia_reunion.html.twig',array('asistentes'=>$asistentes));
    }
    public function asistenciaMingaAction($anio,$mes){
      $asistentes = $this->em->createQueryBuilder()
      ->select('o')
      ->from('AdminBundle\Entity\Asistente','o')
      ->innerJoin('o.reunion','r')
      ->where('MONTH(r.fecha)=:mes and YEAR(r.fecha)=:anio and r.tipo=:tipo')
      ->setParameter('mes',$mes)
      ->setParameter('anio',$anio)
      ->setParameter('tipo',"minga")
      ->orderBy('o.id','DESC')
      ->getQuery()
      ->getResult();
      return $this->render('AdminBundle:Reportes:asistencia_minga.html.twig',array('asistentes'=>$asistentes));
    }
    
    
    /***********Fin Reportes****************/





    private function getPdfOutput($usuario,$motivo,$valor,$id,$totales)
    {
        $em = $this->getDoctrine()->getManager();
        $snappy=$this->get('knp_snappy.pdf');
        $header="";
        $html = $this->renderView('template/riegoheader.html.twig',array('header'=>$header));
        $htmlFooter="";
        $options = [
                   'header-html' => $html,
                   'footer-html' => $htmlFooter,
               ];
         //$content=html_entity_decode($document->getContent());
         $content= $this->renderView('DocumentBundle:templates:receive.html.twig',array("usuario"=>$usuario,"motivo"=>$motivo,"valor"=>$valor,"id"=>$id,"totales"=>$totales));
         $htmlBody= $this->renderView('template/body.html.twig',array('body'=>$content));
         $final_file=$snappy->getOutputFromHtml($htmlBody,$options);

         return $final_file;
    }
    private function generarPdfReciboDePago($usuario,$motivo,$valor,$id,$totales)
    {
       $final_file=$this->getPdfOutput($usuario,$motivo,$valor,$id,$totales);
       $path=$this->container->getParameter('documents');
       $pathToSave= $this->get('kernel')->getRootDir().$path;
       $documentName='documento'. time() . '.pdf';
       file_put_contents($pathToSave.$documentName, $final_file);
       return $documentName;
    }

    public function extraerPdfDesdeDiscoAction($id)
    {
        $path=$this->container->getParameter('documents');
        $pathToSave= $this->get('kernel')->getRootDir().$path;
        $record = $this->em->createQueryBuilder()
                ->select('o')
                ->from("AdminBundle:Cobro",'o')
                ->where('o=:id')
                ->setParameter('id',$id)
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();

        $fileName = $record->getDocumento();
        $final_file=file_get_contents($pathToSave.$fileName, true);
        return new Response
        (
          $final_file,
          200,
          array(
            'Content-Type'=> 'application/pdf',
            'Content-Disposition'=>'inline;filename=temp.pdf'
          )
        );
    }
    private function registrarCobro($registro,$mes=0,$anio=0){
      $usr_id=$registro->user_id;
      $usr_value=$registro->valor;
      $row_id=$registro->id;
      $tipo_cobro=$registro->tipo;

      $user = $this->em->createQueryBuilder()
      ->select('o')
      ->from('SiteConfigurationBundle\Entity\User','o')
      ->where('o=:user_id')
      ->setParameter('user_id',$usr_id)
      ->setMaxResults(1)
      ->getQuery()
      ->getOneOrNullResult();
      $motivo="Pago multa pendiente";
      switch($tipo_cobro){
        case "multa":{
          $registro= $this->em->createQueryBuilder()
          ->select('o')
          ->from('AdminBundle\Entity\Multa','o')
          ->where('o=:row_id')
          ->setParameter('row_id',$row_id)

          ->setMaxResults(1)
          ->getQuery()
          ->getOneOrNullResult();

          $this->em->remove($registro);

          break;
        }
        case "faltas":{
          $motivo="Pago multa por inasistencia";
          $asistentes = $this->em->createQueryBuilder()
          ->select('o')
          ->from('AdminBundle\Entity\Asistente','o')
          ->where('o.usuario=:user_id')
          ->setParameter('user_id',$usr_id)
          ->getQuery()
          ->getResult();
          foreach($asistentes as $asistente){
            $asistente->setCancelado(1);
            $this->em->persist($asistente);
          }

          break;
        }
        default: break;
      }
      

      $cobro=new Cobro();
      $cobro->setUser($user);
      $cobro->setValor($usr_value);
      $cobro->setMotivo($motivo);
      $cobro->setMes($mes);
      $cobro->setAnio($anio);
      $this->em->persist($cobro);
      $cobro->setDocumento("");

      $this->em->flush();
      return true;

    }
    public function registrarCobroMultaAction(Request $request){
        $usr_id=$request->request->get('usr_id');
        $usr_value=$request->request->get('usr_value');
        $row_id=$request->request->get('row_id');
        $tipo_cobro=$request->request->get('tipo_cobro');
        $user = $this->em->createQueryBuilder()
        ->select('o')
        ->from('SiteConfigurationBundle\Entity\User','o')
        ->where('o=:user_id')
        ->setParameter('user_id',$usr_id)
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();
        $motivo="Pago multa pendiente";
        switch($tipo_cobro){
          case "multa":{
            $registro= $this->em->createQueryBuilder()
            ->select('o')
            ->from('AdminBundle\Entity\Multa','o')
            ->where('o=:row_id')
            ->setParameter('row_id',$row_id)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

            $this->em->remove($registro);

            break;
          }
          case "faltas":{
            $motivo="Pago multa por inasistencia";
            $asistentes = $this->em->createQueryBuilder()
            ->select('o')
            ->from('AdminBundle\Entity\Asistente','o')
            ->where('o.usuario=:user_id')
            ->setParameter('user_id',$usr_id)
            ->getQuery()
            ->getResult();
            foreach($asistentes as $asistente){
              $asistente->setCancelado(1);
              $this->em->persist($asistente);
            }

            break;
          }
          default: break;
        }
        
        $t=date('d-m-Y');
        $mes=date("m",strtotime($t));
        $anio=date("Y",strtotime($t));
  
        $cobro=new Cobro();
        $cobro->setUser($user);
        $cobro->setValor($usr_value);
        $cobro->setMes($mes);
        $cobro->setAnio($anio);
        $cobro->setTemporal(0);
        $cobro->setMotivo($motivo);
        $this->em->persist($cobro);
        $cobro->setDocumento("");

        $this->em->flush();

        $nombre_documento=$this->generarPdfReciboDePago($user->getName()." ".$user->getLastname(),$motivo,$usr_value,$cobro->getId(),$usr_value);
        $cobro->setDocumento($nombre_documento);
        $this->em->persist($cobro);
        $this->em->flush();


        return new JsonResponse(["event"=>"success","msg"=>"Actualizado correctamente"]);

    }
    public function registroMultaAction(Request $request){

      $userId=$request->request->get("reasign_to");
      $valor=$request->request->get("valor");
      $mes=$request->request->get("mes");
      $anio=$request->request->get("anio");
      $motivo=$request->request->get("reasign_msg");


      $usuario= $this->em->createQueryBuilder()
      ->select('o')
      ->from('SiteConfigurationBundle\Entity\User','o')
      ->where('o=:user_id')
      ->setParameter('user_id',$userId)
      ->setMaxResults(1)
      ->getQuery()
      ->getOneOrNullResult();

      $verificaMulta= $this->em->createQueryBuilder()
      ->select('count(o)')
      ->from('AdminBundle\Entity\Multa','o')
      ->where('o.mes=:mes')
      ->andWhere('o.anio=:anio')
      ->andWhere('o.valor=10')
      ->setParameter('anio',$anio)
      ->setParameter('mes',$mes)
      ->getQuery()
      ->getSingleScalarResult();


      if($valor!=10||($valor==10&&$verificaMulta==0)){

      if($usuario->getName()=="Todos"){
        $usuarios= $this->em->createQueryBuilder()
        ->select('o')
        ->from('SiteConfigurationBundle\Entity\User','o')
        ->where('o!=:user_id')
        ->setParameter('user_id',$userId)
        ->getQuery()
        ->getResult();
        foreach ($usuarios as $usr){
          $cobroObj=new Multa();
          if((int)$valor==0){
            $cobroObj->setValor(0); 
    
            $date = new \DateTime('now');
            $date->modify('+3 month'); 
            $usuario->setServiceState("Suspendido hasta ".$date->format('Y-m-d h:i:s'));
            $this->em->persist($usr);
            $cobroObj->setMotivo($motivo);
          }
          else{
            $cobroObj->setValor($valor);
            $cobroObj->setMotivo($motivo);
            $cobroObj->setMes($mes);
            $cobroObj->setAnio($anio);
          }
          
          $cobroObj->setUser($usr);
          $cobroObj->setFecha(new \DateTime());
          $this->em->persist($cobroObj);
          $this->em->flush();

        }

      }
      else{
      $cobroObj=new Multa();
      if((int)$valor==0){
        $cobroObj->setValor(0); 

        $date = new \DateTime('now');
        $date->modify('+3 month'); 
        $usuario->setServiceState("Suspendido hasta ".$date->format('Y-m-d h:i:s'));
        $this->em->persist($usuario);
        $cobroObj->setMotivo($motivo);
      }
      else{
        $cobroObj->setValor($valor);
        $cobroObj->setMotivo($motivo);
        $cobroObj->setMes($mes);
        $cobroObj->setAnio($anio);
      }
    
      $cobroObj->setUser($usuario);
      $cobroObj->setFecha(new \DateTime());
      $this->em->persist($cobroObj);
      $this->em->flush();
      }
    }
    else return new JsonResponse(["event"=>"error","msg"=>"Cobro ya Registrado"]);

    return new JsonResponse(["event"=>"success","msg"=>"Actualizado correctamente"]);
    }

    
    public function eliminarMultaAction(Request $request){

      $id=$request->request->get("registro");

      $multa= $this->em->createQueryBuilder()
      ->select('o')
      ->from('AdminBundle\Entity\Multa','o')
      ->where('o=:id')
      ->setParameter('id',$id)
      ->setMaxResults(1)
      ->getQuery()
      ->getOneOrNullResult();

      $this->em->remove($multa);
      $this->em->flush();

      return new JsonResponse(["event"=>"success","msg"=>"Actualizado correctamente"]);
    }

    public function multasAction(Request $request){

      $asistentes = $this->em->createQueryBuilder()
      ->select('p.id,p.name, p.lastname, count(o.id) as faltas')
      ->from('AdminBundle\Entity\Asistente','o')
      ->innerJoin('o.usuario','p')
      ->where('o.cancelado=false')
      ->andwhere('o.presente=false')
      ->groupBy('p.id')
      ->having('count(o.id)>=3')
      ->getQuery()
      ->getResult();

      $multas=$this->em->createQueryBuilder()
      ->select('o')
      ->from('AdminBundle\Entity\Multa','o')
      ->getQuery()
      ->getResult();

      return $this->render('AdminBundle:Users:multas.html.twig',array("asistentes"=>$asistentes,"multas"=>$multas));
    }

    public function reunionesPrincipalAction(Request $request)
    {
      $reuniones = $this->em->createQueryBuilder()
              ->select('o')
              ->from('AdminBundle\Entity\Reunion','o')
              ->orderBy('o.id','DESC')
              ->getQuery()
              ->getResult();

      return $this->render('AdminBundle:Users:asistencia.html.twig',array("reuniones"=>$reuniones));
      
    }
    public function registrarReunionAction(Request $request){
      $fecha= $request->request->get("session_date");
      $motivo= $request->request->get("reasign_msg");
      $tipo= $request->request->get("tipo");

      $reunion= new Reunion();
      $reunion->setFecha(new \DateTime($fecha));
      $reunion->setMotivo($motivo);
      $reunion->setTipo($tipo);
      $this->em->persist($reunion);
      $this->em->flush();

      $usuarios=$this->em->getRepository('SiteConfigurationBundle:User')->findAll();

      foreach($usuarios as $usuario){
        $asistente= new Asistente();
        $asistente->setUsuario($usuario);
        $asistente->setReunion($reunion);
        $this->em->persist($asistente);
      }
      $this->em->flush();

      return new JsonResponse(["event"=>"success","msg"=>"Actualizado correctamente", "id"=>$reunion->getId()]);
    }

    public function edicionAsistenciaAction($id){
      //$id=$request->request->get("id");

      $asistentes = $this->em->createQueryBuilder()
              ->select('o')
              ->from('AdminBundle\Entity\Asistente','o')
              ->where('o.reunion =:id')
              ->setParameter('id',$id)
              ->orderBy('o.id','DESC')
              ->getQuery()
              ->getResult();

      return $this->render('AdminBundle:Users:edicion_asistencia.html.twig',array("asistentes"=>$asistentes,"id"=>$id));

    }

    public function edicionAsistentesAction(Request $request){
      $id=$request->request->get("id_reunion");
      $asisten=json_decode($request->request->get("asistentes"));

      $encerados= $this->em->createQueryBuilder()
      ->select('o')
      ->from('AdminBundle\Entity\Asistente','o')
      ->where('o.reunion=:reunion_id')
      ->setParameter('reunion_id',$id)
      ->getQuery()
      ->getResult();
      foreach($encerados as $encerado)
      {
        $encerado->setPresente(0);
        $this->em->persist($encerado);
      }
      $this->em->flush();


      $asistentes= $this->em->createQueryBuilder()
      ->select('o')
      ->from('AdminBundle\Entity\Asistente','o')
      ->where('o.usuario in (:asisten)')
      ->andWhere('o.reunion=:reunion_id')
      ->setParameter('asisten',$asisten)
      ->setParameter('reunion_id',$id)
      ->getQuery()
      ->getResult();


      foreach($asistentes as $asistente)
      {
        $asistente->setPresente(1);
        $this->em->persist($asistente);
      }
      $this->em->flush();

      return new JsonResponse(["event"=>"success","msg"=>"Actualizado Correctamente"]);
    }
    private function registrarPagoIndividual($motivo,$valor,$userId){

      $usuario= $this->em->createQueryBuilder()
      ->select('o')
      ->from('SiteConfigurationBundle\Entity\User','o')
      ->where('o=:user_id')
      ->setParameter('user_id',$userId)
      ->setMaxResults(1)
      ->getQuery()
      ->getOneOrNullResult();

      $t=date('d-m-Y');
      $mes=date("m",strtotime($t));
      $anio=date("Y",strtotime($t));

      $cobroObj=new Cobro();
      $cobroObj->setValor($valor);
      $cobroObj->setMes($mes);
      $cobroObj->setAnio($anio);
      $cobroObj->setMotivo($motivo);
      $cobroObj->setTemporal(0);
      $cobroObj->setUser($usuario);
      $cobroObj->setDocumento("");
      $this->em->persist($cobroObj);
      $this->em->flush();
      return $cobroObj;
    }

    public function registrarPagoAction(Request $request){
      $valores=$request->request->get("valores");
      $totales=json_decode($valores);
      $principal=$request->request->get("principal");
      $principal_decoded=json_decode($principal);

      $mes=$principal_decoded->mes;
      $anio=$principal_decoded->anio;
      $valoresTotales=0;
      foreach($totales as $total){
        $this->registrarCobro($total,$mes,$anio);
        $valoresTotales+=$total->valor;
      }

      $cobroObj=$this->registrarPagoIndividual($principal_decoded->motivo,
      $principal_decoded->valor+$valoresTotales,
      $principal_decoded->usuario);
      
      
      $user = $this->em->createQueryBuilder()
              ->select('o')
              ->from('SiteConfigurationBundle\Entity\User','o')
              ->where('o=:user_id')
              ->setParameter('user_id',$principal_decoded->usuario)
              ->setMaxResults(1)
              ->getQuery()
              ->getOneOrNullResult();

      

      $nombre_documento=$this->generarPdfReciboDePago($user->getName()." ".$user->getLastname(),$principal_decoded->motivo,$principal_decoded->valor,$cobroObj->getId(),$totales);
        $cobroObj->setDocumento($nombre_documento);
        $cobroObj->setMes($mes);
        $cobroObj->setAnio($anio);
        $this->em->persist($cobroObj);
        $this->em->flush();


      return new JsonResponse(["event"=>"success","msg"=>"Actualizado correctamente"]);
    }
    public function cobrosAction(Request $request){
      $cobros = $this->em->createQueryBuilder()
              ->select('o')
              ->from('AdminBundle\Entity\Cobro','o')
              ->where('o.temporal=0')
              ->orderBy('o.id','DESC')
              ->getQuery()
              ->getResult();
      return $this->render('AdminBundle:Users:cobros.html.twig',["cobros"=>$cobros]);
    }
    public function obtenerMultasAction(Request $request){
      $id=$request->request->get('id');
      $cobros = $this->em->createQueryBuilder()
            ->select("o.id,o.valor, o.motivo, 'multa' as tipo")
            ->from('AdminBundle\Entity\Multa','o')
            ->where('o.user=:user_id')
            ->setParameter('user_id',$id)
            ->getQuery()
            ->getArrayResult();

      $asistentes = $this->em->createQueryBuilder()
            ->select("0 as id,count(o.id) as valor,'Faltas por cancelar'as motivo,'faltas'as tipo")
            ->from('AdminBundle\Entity\Asistente','o')
            ->innerJoin('o.usuario','p')
            ->where('o.cancelado=false')
            ->andwhere('o.presente=false')
            ->andwhere('p.id=:user_id')
            ->setParameter('user_id',$id)
            ->groupBy('p.id')
            ->having('count(o.id)>=3')
            ->getQuery()
            ->getArrayResult();
      $resultadoFinal=array_merge($cobros,$asistentes);

      return new JsonResponse($resultadoFinal);
    }

    public function removeUserAction(Request $request)
    {
      $userId=$request->request->get('document_id');
      $user = $this->em->createQueryBuilder()
              ->select('o')
              ->from('SiteConfigurationBundle\Entity\User','o')
              ->where('o=:user_id')
              ->setParameter('user_id',$userId)
              ->setMaxResults(1)
              ->getQuery()
              ->getOneOrNullResult();
      $this->em->remove($user);
      $this->em->flush();
      return new JsonResponse(array("event"=>"success","msg"=>"Usuario Borrado"));

    }
    public function showUsersAction(Request $request)
    {
      $users = $this->em->createQueryBuilder()
              ->select('o.id,o.name,o.lastname,o.email,o.enabled,o.image, o.serviceState')
              ->from('SiteConfigurationBundle\Entity\User','o')
              ->orderBy('o.lastname','ASC')
              ->getQuery()
              ->getResult();
      return $this->render('AdminBundle:Users:show_users.html.twig',array("users"=>$users));
    }
    public function updateUserImageAction(Request $request){
      $user_id = $request->get('user');
      $user = $this->em->createQueryBuilder()
              ->select('o')
              ->from('SiteConfigurationBundle\Entity\User','o')
              ->where('o=:user_id')
              ->setParameter('user_id',$user_id)
              ->getQuery()
              ->getSingleResult();
      $img=$request->get('img');
      $user->setImage($img);
      $this->em->persist($user);
      $this->em->flush();
      $result=array("event"=>"success","msg"=>"Updated Succesfully");
      return new JsonResponse($result);
    }
    public function updateRoleAction(Request $request){
      $roles=$request->request->get('roles');
      $user_id=$request->request->get('user_id');

      $roles=$request->request->get('roles');
      $user = $this->em->createQueryBuilder()
              ->select('o')
              ->from('SiteConfigurationBundle\Entity\User','o')
              ->where('o=:user_id')
              ->setParameter('user_id',$user_id)
              ->getQuery()
              ->getSingleResult();

      $user->setRoles($roles);
      $this->em->persist($user);
      $this->em->flush();

      return new JsonResponse("hola");

    }
    public function userEventAction(Request $request)
    {
      $redirect=0;
      $abreviation=$request->request->get('abreviation');
      $position=$request->request->get('position');
      $user_id=$request->request->get('user_id');
      $areaId=$request->request->get('area');
      $profile=$request->request->get('profile');
      $name=$request->request->get('name');
      $lastname=$request->request->get('lastname');
      $nickname=$request->request->get('nick_name');
      $this->email=$request->request->get('email');
      $gender=$request->request->get('gender');
      $selected_roles=json_decode($request->request->get('selected_roles'));
      $state=$request->request->get('state');
      $creation_mode=(int)$request->request->get('creation_mode');
      $area=$this->em->getRepository('AdminBundle:Area')->findOneBy(array('id' => $areaId));

      $user = $this->em->createQueryBuilder()
              ->select('o')
              ->from('SiteConfigurationBundle\Entity\User','o')
              ->where('o=:user_id')
              ->setParameter('user_id',$user_id)
              ->setMaxResults(1)
              ->getQuery()
              ->getOneOrNullResult();
      $is_user_already_created=$user!=null?true:false;

      if(!$is_user_already_created){
        $user=new User();
        $user->setPlainPassword($nickname);
        $user->setCompany($this->company);
        $redirect=1;
      }
      $user->setEmail($this->email);
      $user->setUsername($nickname);
      $user->setLastName($lastname);
      $user->setName($name);
      $user->setPlainPassword($nickname);
      $user->setRoles($selected_roles);
      $user->setEnabled($state);
      $user->setArea($area);
      $user->setAbreviation($abreviation);
      $user->setPosition($position);
      try{
        $this->em->persist($user);
        $this->em->flush();
      } catch (\Doctrine\DBAL\DBALException $ex) {
            if ($ex->getPrevious() &&  0 === strpos($ex->getPrevious()->getCode(), '23')) {
              return new JsonResponse(array("event"=>"error", "msg"=>"Usuario ya registrado. Revise usuario y correo"));
             }
      }
      return new JsonResponse (array("creation"=>0,"event"=>"success","msg"=>"Usuario actualizado de manera exitosa..","user_id"=>$user->getId(),"redirect"=>$redirect));
    }


    public function getUserListAction(Request $request)
    {
        $users = $this->em->createQueryBuilder()
                ->select('o.id,o.name,o.lastname,o.email,o.enabled')
                ->from('SiteConfigurationBundle\Entity\User','o')
              //  ->where('o.storeactivation=0')
              //  ->orderBy('o.price','ASC')
                ->getQuery()
                ->getResult();

        return new JsonResponse($users);
    }
    /*Creation or Insertion Page*/
    public function userUpdateorInsertAction(Request $request)
    {
      $flag=(int)$request->request->get('create');
      $user_id=(int)$request->attributes->get('userid');
      $this->get('session')->set('profile_id', $user_id);
      $creation_mode=$flag==1?1:0;
      $user=null;
      $assigned_roles=array();

      $areas = $this->em->createQueryBuilder()
              ->select('o')
              ->from('AdminBundle\Entity\Area','o')
              ->where('o.parent is not NULL')
              ->andWhere('o.company=:company')
              ->setParameter('company',$this->company->getId())
              ->orderBy('o.name','ASC')
              ->getQuery()
              ->getResult();
      if (($creation_mode==0)&&($user_id==0))$creation_mode=1;
      if($user_id<>0){
        $user = $this->em->createQueryBuilder()
                ->select('o')
                ->from('SiteConfigurationBundle\Entity\User','o')
                ->where('o=:user_id')
                ->setParameter('user_id',$user_id)
                ->getQuery()
                ->getSingleResult();
         $assigned_roles=$user->getRoles();
      }
      $roles = $this->em->createQueryBuilder()
              ->select('o')
              ->from('SiteConfigurationBundle\Entity\Roles','o')
              ->where('o.isActive=1')
              ->orderBy('o.description','ASC')
              ->getQuery()
              ->getResult();


       return $this->render('AdminBundle:Users:user_creation.html.twig',array("assigned_roles"=>$assigned_roles,"roles"=>$roles,"creation_mode"=>$creation_mode,"user"=>$user,"areas"=>$areas));
    }

    /*This function is updating basic user fields through async mode*/
    public function updateAsyncUserAction(Request $request)
    {
      $id = $request->request->get('id');
      $name=$request->request->get('name');
      $lastname=$request->request->get('lastname');
      $this->email=$request->request->get('email');
      $enabled=$request->request->get('enabled')?1:0;

      $area=$this->em->getRepository('AdminBundle:Area')->findOneBy(array('id' => $areaId));
      $user = $this->em->createQueryBuilder()
              ->select('o')
              ->from('DashboardBundle\Entity\User','o')
              ->where('o=:id')
              ->setParameter('id',$id)
              ->getQuery()
              ->getSingleResult();

        $user->setName($name);
        $user->setLastname($lastname);
        $user->setEmail($this->email);
        $user->setEnabled($enabled);
        $this->em->persist($user);
        $this->em->flush();
        return new JsonResponse("hola");
    }


}
