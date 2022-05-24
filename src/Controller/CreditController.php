<?php

namespace App\Controller;

use App\Form\AcordareCreditFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CreditController extends AbstractController
{
    /**
     * @Route("/", name="app_homepage")
     */
    public function homepage()
    {
        return $this->render('informatii/homepage.html.twig');
    }

    /**
     * @Route("/rezultate", name="app_rezultate")
     */
    public function rezultate()
    {
        return $this->render('rezultate/rezultate.html.twig');
    }

    /**
     * @Route("/simulare", name="app_simulare_credit")
     */
    public function show(Request $request)
    {
        $form = $this->createForm(AcordareCreditFormType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $salariulAnual = (int)$data['salariu'];
            $venitAnual =(int)$data['venit'];

            $data = $this->expresiaAnaliticaSA($data, $salariulAnual);
            $data = $this->expresiaAnaliticaVA($data, $venitAnual);

            $data = $this->definireReguli($data);

            $rezultat = $this->exprimareReguliSubMetodaMinimului($data);

            $media_maximului = $this->metodaMetodaMedieiMaximului($rezultat);

            $rezultat_hdm = $this->metodaSegmentelorOrizontale($rezultat);

            return $this->render('rezultate/rezultate.html.twig',
                array(
                    'media_maximului' => $media_maximului,
                    'rezultat_hdm'    => $rezultat_hdm
                    ));
        }

        return $this->render('informatii/simulare.html.twig',[
            'simulareForm' =>$form->createView(),
        ]);
    }

    /**
     * Expresia analitica a functiilor de apartenenta a variabilei lingvistice SA
     *
     * @param $data
     * @param $salariulAnual
     * @return mixed
     */
    public function expresiaAnaliticaSA($data, $salariulAnual)
    {
        if ($salariulAnual <= 20000 && $salariulAnual >= 0) {
            $data['grad_apartenenta_s_scazut'] = 1 ;
        } elseif ($salariulAnual >= 20000 && $salariulAnual <= 50000) {
            $data['grad_apartenenta_s_scazut'] = (50000 - $salariulAnual) / 30000;
        }

        if ($salariulAnual <= 50000 && $salariulAnual >= 20000) {
            $data['grad_apartenenta_s_mediu'] = ($salariulAnual - 20000) / 30000 ;
        } elseif ($salariulAnual >= 50000 && $salariulAnual <= 80000) {
            $data['grad_apartenenta_s_mediu'] = (80000 - $salariulAnual) / 30000;
        }

        if ($salariulAnual <= 80000 && $salariulAnual >= 50000) {
            $data['grad_apartenenta_s_ridicat'] = ($salariulAnual - 50000) / 30000 ;
        } elseif ($salariulAnual >= 80000 && $salariulAnual <= 100000) {
            $data['grad_apartenenta_s_ridicat'] = 1;
        }

        return $data;
    }

    /**
     * Expresia analitica a functiilor de apartenenta a variabilei lingvistice VA
     *
     * @param array $data
     * @param $venitAnual
     * @return array
     */
    public function expresiaAnaliticaVA(array $data, $venitAnual)
    {
        if ($venitAnual <= 200000 && $venitAnual >= 0) {
            $data['grad_apartenenta_v_scazut'] = 1 ;
        } elseif ($venitAnual >= 200000 && $venitAnual <= 500000) {
            $data['grad_apartenenta_v_scazut'] = (500000 - $venitAnual) / 300000;
        }

        if ($venitAnual <= 500000 && $venitAnual >= 200000) {
            $data['grad_apartenenta_v_mediu'] = ($venitAnual - 200000) / 300000 ;
        } elseif ($venitAnual >= 500000 && $venitAnual <= 800000) {
            $data['grad_apartenenta_v_mediu'] = (800000 - $venitAnual) / 300000;
        }

        if ($venitAnual <= 800000 && $venitAnual >= 500000) {
            $data['grad_apartenenta_v_ridicat'] = ($venitAnual - 500000) / 030000 ;
        } elseif ($venitAnual >= 800000 && $venitAnual <= 1000000) {
            $data['grad_apartenenta_v_ridicat'] = 1;
        }

        return $data;
    }

    /**
     * Definire reguli care au concluzia exprimata in termenii variabilei lingvistice de iesire riscul de creditatre (RC)
     *
     * @param array $data
     * @return array
     */
    public function definireReguli(array $data)
    {
        if (!empty($data['grad_apartenenta_s_scazut']) && !empty($data['grad_apartenenta_v_scazut'])) {
            $data['RC_ridicat_SA_scazut_VA_scazut'] = 1;
        }
        if (!empty($data['grad_apartenenta_s_scazut']) && !empty($data['grad_apartenenta_v_mediu'])) {
            $data['RC_ridicat_SA_scazut_VA_mediu'] = 1;
        }
        if (!empty($data['grad_apartenenta_s_scazut']) && !empty($data['grad_apartenenta_v_ridicat'])) {
            $data['RC_mediu_SA_scazut_VA_ridicat'] = 1;
        }
        if (!empty($data['grad_apartenenta_s_mediu']) && !empty($data['grad_apartenenta_v_scazut'])) {
            $data['RC_ridicat_SA_mediu_VA_scazut'] = 1;
        }
        if (!empty($data['grad_apartenenta_s_mediu']) && !empty($data['grad_apartenenta_v_mediu'])) {
            $data['RC_mediu_SA_mediu_VA_mediu'] = 1;
        }
        if (!empty($data['grad_apartenenta_s_mediu']) && !empty($data['grad_apartenenta_v_ridicat'])) {
            $data['RC_scazut_SA_mediu_VA_redicat'] = 1;
        }
        if (!empty($data['grad_apartenenta_s_ridicat']) && !empty($data['grad_apartenenta_v_scazut'])) {
            $data['RC_mediu_SA_ridicat_VA_scazut'] = 1;
        }
        if (!empty($data['grad_apartenenta_s_ridicat']) && !empty($data['grad_apartenenta_v_mediu'])) {
            $data['RC_scazut_SA_ridicat_VA_mediu'] = 1;
        }
        if (!empty($data['grad_apartenenta_s_ridicat']) && !empty($data['grad_apartenenta_v_ridicat'])) {
            $data['RC_scazut_SA_ridicat_VA_ridicat'] = 1;
        }

        return $data;
    }

    /**
     * Exprimarea celor 9 reguli sub metoda minimului
     *
     * @param array $data
     * @return array
     */
    public function exprimareReguliSubMetodaMinimului(array $data)
    {
        if (!empty($data['grad_apartenenta_s_scazut']) && !empty($data['grad_apartenenta_v_scazut'])) {
            $rezultat['min_RC_ridicat_SA_scazut_VA_scazut'] = min($data['grad_apartenenta_s_scazut'],$data['grad_apartenenta_v_scazut']);
        }
        if (!empty($data['grad_apartenenta_s_scazut']) && !empty($data['grad_apartenenta_v_mediu'])) {
            $rezultat['min_RC_ridicat_SA_scazut_VA_mediu'] = min($data['grad_apartenenta_s_scazut'],$data['grad_apartenenta_v_mediu']);
        }
        if (!empty($data['grad_apartenenta_s_scazut']) && !empty($data['grad_apartenenta_v_ridicat'])) {
            $rezultat['min_RC_mediu_SA_scazut_VA_ridicat'] = min($data['grad_apartenenta_s_scazut'],$data['grad_apartenenta_v_ridicat']);
        }
        if (!empty($data['grad_apartenenta_s_mediu']) && !empty($data['grad_apartenenta_v_scazut'])) {
            $rezultat['min_RC_ridicat_SA_mediu_VA_scazut'] = min($data['grad_apartenenta_s_mediu'],$data['grad_apartenenta_v_scazut']);
        }
        if (!empty($data['grad_apartenenta_s_mediu']) && !empty($data['grad_apartenenta_v_mediu'])) {
            $rezultat['min_RC_mediu_SA_mediu_VA_mediu'] = min($data['grad_apartenenta_s_mediu'],$data['grad_apartenenta_v_mediu']);
        }
        if (!empty($data['grad_apartenenta_s_mediu']) && !empty($data['grad_apartenenta_v_ridicat'])) {
            $rezultat['min_RC_scazut_SA_mediu_VA_redicat'] = min($data['grad_apartenenta_s_mediu'],$data['grad_apartenenta_v_ridicat']);
        }
        if (!empty($data['grad_apartenenta_s_ridicat']) && !empty($data['grad_apartenenta_v_scazut'])) {
            $rezultat['min_RC_mediu_SA_ridicat_VA_scazut'] = min($data['grad_apartenenta_s_ridicat'],$data['grad_apartenenta_v_scazut']);
        }
        if (!empty($data['grad_apartenenta_s_ridicat']) && !empty($data['grad_apartenenta_v_mediu'])) {
            $rezultat['min_RC_scazut_SA_ridicat_VA_mediu'] = min($data['grad_apartenenta_s_ridicat'],$data['grad_apartenenta_v_mediu']);
        }
        if (!empty($data['grad_apartenenta_s_ridicat']) && !empty($data['grad_apartenenta_v_ridicat'])) {
            $rezultat['min_RC_scazut_SA_ridicat_VA_ridicat'] = min($data['grad_apartenenta_s_ridicat'],$data['grad_apartenenta_v_ridicat']);
        }

        return $rezultat;
    }

    /**
     * Calcularea riscului de creditare prin metoda mediei maximului
     *
     * @param array $rezultat
     * @return float|int
     */
    public function metodaMetodaMedieiMaximului(array $rezultat)
    {
        $highest = 0;
        $index = '';
        foreach ($rezultat as $key => $value) {
            if ($value > $highest) {
                $highest = $value;
                $index = $key;
            }
        }

        if (!empty($rezultat['min_RC_ridicat_SA_scazut_VA_scazut']) && $highest == $rezultat['min_RC_ridicat_SA_scazut_VA_scazut']
        ) {
            $media_maximului = (15+20)/2;
        } elseif (!empty($rezultat['min_RC_ridicat_SA_scazut_VA_mediu']) && $highest == $rezultat['min_RC_ridicat_SA_scazut_VA_mediu']
        ) {
            $media_maximului = (15+20)/2;
        } elseif (!empty($rezultat['min_RC_ridicat_SA_mediu_VA_scazut']) && $highest == $rezultat['min_RC_ridicat_SA_mediu_VA_scazut']
        ) {
            $media_maximului = (15+20)/2;
        } elseif (!empty($rezultat['min_RC_mediu_SA_scazut_VA_ridicat']) && $highest == $rezultat['min_RC_mediu_SA_scazut_VA_ridicat']
        ) {
            $media_maximului = (8+12)/2;
        } elseif (!empty($rezultat['min_RC_mediu_SA_mediu_VA_mediu']) && $highest == $rezultat['min_RC_mediu_SA_mediu_VA_mediu']
        ) {
            $media_maximului = (8+12)/2;
        } elseif (!empty($rezultat['min_RC_mediu_SA_ridicat_VA_scazut']) && $highest == $rezultat['min_RC_mediu_SA_ridicat_VA_scazut']
        ) {
            $media_maximului = (8+12)/2;
        } elseif (!empty($rezultat['min_RC_scazut_SA_mediu_VA_redicat']) && $highest == $rezultat['min_RC_scazut_SA_mediu_VA_redicat']
        ) {
            $media_maximului = 6/2;
        } elseif (!empty($rezultat['min_RC_scazut_SA_ridicat_VA_mediu']) && $highest == $rezultat['min_RC_scazut_SA_ridicat_VA_mediu']
        ) {
            $media_maximului = 6/2;
        } elseif (!empty($rezultat['min_RC_scazut_SA_ridicat_VA_ridicat']) && $highest == $rezultat['min_RC_scazut_SA_ridicat_VA_ridicat']
        ) {
            $media_maximului = 6/2;
        }

        return $media_maximului;
    }


    /**
     * Calculare risc de creditare prin metoda segmentelor orizontale
     *
     * @param array $rezultat
     * @return float|int
     */
    public function metodaSegmentelorOrizontale(array $rezultat)
    {
        // risc ridicat
        if (!empty($rezultat['min_RC_ridicat_SA_scazut_VA_scazut'])
        ) {
            $metoda_segmentelor_ridicat = $rezultat['min_RC_ridicat_SA_scazut_VA_scazut'] * (15 + 20) / 2;
            $valoare_risc_ridicat = $rezultat['min_RC_ridicat_SA_scazut_VA_scazut'];
        } elseif (!empty($rezultat['min_RC_ridicat_SA_scazut_VA_mediu'])
        ) {
            $metoda_segmentelor_ridicat = $rezultat['min_RC_ridicat_SA_scazut_VA_mediu'] * (15 + 20) / 2;
            $valoare_risc_ridicat = $rezultat['min_RC_ridicat_SA_scazut_VA_mediu'];
        } elseif (!empty($rezultat['min_RC_ridicat_SA_mediu_VA_scazut'])
        ) {
            $metoda_segmentelor_ridicat = $rezultat['min_RC_ridicat_SA_mediu_VA_scazut'] * (15 + 20) / 2;
            $valoare_risc_ridicat = $rezultat['min_RC_ridicat_SA_mediu_VA_scazut'];
        }

        // risc mediu

        if (!empty($rezultat['min_RC_mediu_SA_scazut_VA_ridicat'])
        ) {
            $metoda_segmentelor_mediu = $rezultat['min_RC_mediu_SA_scazut_VA_ridicat'] * (8 + 12) / 2;
            $valoare_risc_mediu = $rezultat['min_RC_mediu_SA_scazut_VA_ridicat'];
        } elseif (!empty($rezultat['min_RC_mediu_SA_mediu_VA_mediu'])
        ) {
            $metoda_segmentelor_mediu = $rezultat['min_RC_mediu_SA_mediu_VA_mediu'] * (8 + 12) / 2;
            $valoare_risc_mediu = $rezultat['min_RC_mediu_SA_mediu_VA_mediu'];
        } elseif (!empty($rezultat['min_RC_mediu_SA_ridicat_VA_scazut'])
        ) {
            $metoda_segmentelor_mediu = $rezultat['min_RC_mediu_SA_ridicat_VA_scazut'] * (8 + 12) / 2;
            $valoare_risc_mediu = $rezultat['min_RC_mediu_SA_ridicat_VA_scazut'];
        }

        //risc scazut

        if (!empty($rezultat['min_RC_scazut_SA_mediu_VA_redicat'])
        ) {
            $metoda_segmentelor_scazut = $rezultat['min_RC_scazut_SA_mediu_VA_redicat'] * (0 + 6) / 2;
            $valoare_risc_scazut = $rezultat['min_RC_scazut_SA_mediu_VA_redicat'];
        } elseif (!empty($rezultat['min_RC_scazut_SA_ridicat_VA_mediu'])
        ) {
            $metoda_segmentelor_scazut = $rezultat['min_RC_scazut_SA_ridicat_VA_mediu'] * (0 + 6) / 2;
            $valoare_risc_scazut = $rezultat['min_RC_scazut_SA_ridicat_VA_mediu'];
        } elseif (!empty($rezultat['min_RC_scazut_SA_ridicat_VA_ridicat'])
        ) {
            $metoda_segmentelor_scazut = $rezultat['min_RC_scazut_SA_ridicat_VA_ridicat'] * (0 + 6) / 2;
            $valoare_risc_scazut = $rezultat['min_RC_scazut_SA_ridicat_VA_ridicat'];
        }

        if (isset($metoda_segmentelor_ridicat)  && isset($metoda_segmentelor_mediu) && isset($metoda_segmentelor_scazut)) {
            $rezultat_hdm = ($metoda_segmentelor_scazut +  $metoda_segmentelor_mediu + $metoda_segmentelor_ridicat)
                / ($valoare_risc_scazut + $valoare_risc_mediu + $valoare_risc_ridicat);
        } elseif( !isset($metoda_segmentelor_scazut) && isset($metoda_segmentelor_ridicat) && isset($metoda_segmentelor_mediu)) {
            $rezultat_hdm = ( $metoda_segmentelor_mediu + $metoda_segmentelor_ridicat) / ( $valoare_risc_mediu + $valoare_risc_ridicat);
        } elseif( isset($metoda_segmentelor_scazut) && !isset($metoda_segmentelor_ridicat) && isset($metoda_segmentelor_mediu)) {
            $rezultat_hdm = ( $metoda_segmentelor_mediu + $metoda_segmentelor_scazut) / ( $valoare_risc_mediu + $valoare_risc_scazut);
        } elseif( isset($metoda_segmentelor_scazut) && isset($metoda_segmentelor_ridicat) && !isset($metoda_segmentelor_mediu)) {
            $rezultat_hdm = ( $metoda_segmentelor_ridicat + $metoda_segmentelor_scazut) / ( $valoare_risc_ridicat + $valoare_risc_scazut);
        } elseif( isset($metoda_segmentelor_scazut) && !isset($metoda_segmentelor_ridicat) && !isset($metoda_segmentelor_mediu)) {
            $rezultat_hdm = ( $metoda_segmentelor_scazut) / ( $valoare_risc_scazut);
        } elseif( !isset($metoda_segmentelor_scazut) && isset($metoda_segmentelor_ridicat) && !isset($metoda_segmentelor_mediu)) {
            $rezultat_hdm = ( $metoda_segmentelor_ridicat ) / ( $valoare_risc_ridicat );
        } elseif( !isset($metoda_segmentelor_scazut) && !isset($metoda_segmentelor_ridicat) && isset($metoda_segmentelor_mediu)) {
            $rezultat_hdm = ( $metoda_segmentelor_mediu ) / ( $valoare_risc_mediu );
        }

        return $rezultat_hdm;
    }
}