export type CareerChoice = "A" | "B" | "C" | "D";

export interface StudyGrade {
  subject: string;
  grade: number;
}

export interface CareerProfile {
  title: string;
  description: string;
  careers: Array<{ title: string; description: string; skills: string[] }>;
  country: { name: string; flag: string; reason: string; href: string };
}

export const careerProfiles: Record<CareerChoice, CareerProfile> = {
  A: {
    title: "Gestor/Administrativo",
    description:
      "Você tem um perfil organizado, estratégico e gosta de planejar e coordenar atividades. Sua força está na capacidade de tomar decisões baseadas em dados e gerenciar pessoas e recursos de forma eficiente.",
    country: {
      name: "Alemanha",
      flag: "🇩🇪",
      reason:
        "A Alemanha é conhecida por sua excelência em gestão empresarial, engenharia de processos e inovação organizacional. O país oferece programas de MBA de classe mundial e é sede de muitas multinacionais. A cultura alemã valoriza a precisão, organização e eficiência, características ideais para profissionais administrativos.",
      href: "/guias-paises/alemanha",
    },
    careers: [
      {
        title: "Administração de Empresas",
        description:
          "Profissionais de administração planejam, coordenam e dirigem atividades em organizações. Eles podem trabalhar em diversos setores como finanças, marketing, recursos humanos ou operações.",
        skills: ["Liderança", "Planejamento", "Análise de dados", "Comunicação", "Tomada de decisão"],
      },
      {
        title: "Gestão de Projetos",
        description:
          "Gestores de projetos são responsáveis por planejar, executar e monitorar projetos, garantindo que sejam concluídos dentro do prazo, orçamento e escopo definidos.",
        skills: [
          "Planejamento",
          "Organização",
          "Gestão de tempo",
          "Resolução de problemas",
          "Comunicação",
        ],
      },
      {
        title: "Consultoria Empresarial",
        description:
          "Consultores empresariais ajudam organizações a melhorar seu desempenho, analisando problemas existentes e desenvolvendo planos para melhorias.",
        skills: [
          "Análise crítica",
          "Resolução de problemas",
          "Comunicação",
          "Pensamento estratégico",
          "Liderança",
        ],
      },
    ],
  },
  B: {
    title: "Prático/Operacional",
    description:
      "Você tem um perfil prático, gosta de trabalhar com as mãos e ver resultados tangíveis. Sua força está na capacidade de executar tarefas com precisão e resolver problemas de forma direta e eficiente.",
    country: {
      name: "Canadá",
      flag: "🇨🇦",
      reason:
        "O Canadá é reconhecido mundialmente por sua excelência em engenharia, tecnologia e inovação prática. O país oferece programas de engenharia de alta qualidade e tem uma forte indústria de tecnologia. A cultura canadense valoriza o trabalho prático, a inovação e a sustentabilidade, perfeito para profissionais operacionais.",
      href: "/guias-paises/canada",
    },
    careers: [
      {
        title: "Engenharia",
        description:
          "Engenheiros aplicam princípios científicos e matemáticos para desenvolver soluções técnicas para problemas práticos. Existem diversas especializações como civil, mecânica, elétrica, entre outras.",
        skills: [
          "Raciocínio lógico",
          "Resolução de problemas",
          "Trabalho em equipe",
          "Criatividade",
          "Conhecimento técnico",
        ],
      },
      {
        title: "Arquitetura e Urbanismo",
        description:
          "Arquitetos e urbanistas projetam edifícios, espaços públicos e cidades, combinando funcionalidade, estética e sustentabilidade.",
        skills: [
          "Criatividade",
          "Conhecimento técnico",
          "Visão espacial",
          "Gestão de projetos",
          "Comunicação",
        ],
      },
      {
        title: "Saúde (áreas práticas)",
        description:
          "Profissionais de saúde como enfermeiros, fisioterapeutas e técnicos de saúde trabalham diretamente com pacientes, aplicando conhecimentos práticos para cuidar da saúde e bem-estar.",
        skills: [
          "Empatia",
          "Conhecimento técnico",
          "Resolução de problemas",
          "Comunicação",
          "Trabalho em equipe",
        ],
      },
    ],
  },
  C: {
    title: "Criativo/Artístico",
    description:
      "Você tem um perfil criativo, gosta de expressar ideias e emoções através de diversas formas. Sua força está na capacidade de pensar fora da caixa e criar soluções originais e inovadoras.",
    country: {
      name: "França",
      flag: "🇫🇷",
      reason:
        "A França é o berço da arte, design e cultura mundial. Paris é considerada a capital da moda e do design, oferecendo inúmeras oportunidades para profissionais criativos. O país tem uma rica tradição artística e é sede de importantes museus, escolas de arte e empresas criativas.",
      href: "/guias-paises/franca",
    },
    careers: [
      {
        title: "Design",
        description:
          "Designers criam soluções visuais e funcionais para produtos, serviços e comunicações. Existem diversas áreas como design gráfico, de produto, de interiores, entre outras.",
        skills: [
          "Criatividade",
          "Comunicação visual",
          "Software de design",
          "Pesquisa",
          "Trabalho em equipe",
        ],
      },
      {
        title: "Artes e Entretenimento",
        description:
          "Profissionais de artes e entretenimento incluem artistas plásticos, músicos, atores, diretores e outros que criam produções artísticas para entreter e inspirar o público.",
        skills: [
          "Talento artístico",
          "Expressão criativa",
          "Disciplina",
          "Resiliência",
          "Comunicação",
        ],
      },
      {
        title: "Publicidade e Marketing",
        description:
          "Profissionais de publicidade e marketing criam campanhas para promover produtos, serviços e marcas, combinando criatividade com estratégias de comunicação.",
        skills: [
          "Criatividade",
          "Comunicação",
          "Pesquisa de mercado",
          "Trabalho em equipe",
          "Pensamento estratégico",
        ],
      },
    ],
  },
  D: {
    title: "Cientista/Pesquisador",
    description:
      "Você tem um perfil analítico, curioso e gosta de investigar e descobrir novos conhecimentos. Sua força está na capacidade de pensar criticamente, analisar dados e buscar soluções baseadas em evidências.",
    country: {
      name: "Estados Unidos",
      flag: "🇺🇸",
      reason:
        "Os Estados Unidos são líderes mundiais em pesquisa científica e inovação tecnológica. O país abriga universidades de pesquisa conhecidas, como MIT, Harvard e Stanford. A cultura americana valoriza a inovação, o empreendedorismo e a descoberta científica, oferecendo oportunidades para pesquisadores.",
      href: "/guias-paises/eua",
    },
    careers: [
      {
        title: "Ciências da Saúde",
        description:
          "Profissionais como médicos, biomédicos e farmacêuticos pesquisam e aplicam conhecimentos científicos para diagnosticar, tratar e prevenir doenças.",
        skills: [
          "Raciocínio lógico",
          "Pesquisa",
          "Análise de dados",
          "Ética profissional",
          "Comunicação",
        ],
      },
      {
        title: "Pesquisa Científica",
        description:
          "Pesquisadores científicos investigam fenômenos naturais e sociais, desenvolvendo teorias e aplicações práticas para diversos campos do conhecimento.",
        skills: [
          "Pensamento crítico",
          "Metodologia científica",
          "Análise de dados",
          "Persistência",
          "Comunicação acadêmica",
        ],
      },
      {
        title: "Tecnologia da Informação",
        description:
          "Profissionais de TI desenvolvem, implementam e gerenciam sistemas de informação, software e infraestruturas tecnológicas para resolver problemas e otimizar processos.",
        skills: [
          "Lógica de programação",
          "Resolução de problemas",
          "Aprendizado contínuo",
          "Trabalho em equipe",
          "Pensamento analítico",
        ],
      },
    ],
  },
};

export function calculateGpa(grades: StudyGrade[]): number | null {
  if (
    grades.length === 0 ||
    grades.some(({ grade }) => !Number.isFinite(grade) || grade < 0 || grade > 10)
  ) {
    return null;
  }
  const average = grades.reduce((total, { grade }) => total + (grade / 10) * 4, 0) / grades.length;
  return Math.round((average + Number.EPSILON) * 100) / 100;
}

export function getCareerProfile(
  answers: Partial<Record<number, CareerChoice>>,
): CareerProfile | null {
  const counts: Record<CareerChoice, number> = { A: 0, B: 0, C: 0, D: 0 };
  for (const answer of Object.values(answers)) {
    if (answer) {
      counts[answer] += 1;
    }
  }
  const winner = (Object.keys(counts) as CareerChoice[]).reduce((best, choice) =>
    counts[choice] > counts[best] ? choice : best,
  );
  return counts[winner] > 0 ? careerProfiles[winner] : null;
}

export function scoreInterview(answers: string[]): number {
  return answers.reduce((score, answer) => {
    const length = answer.trim().length;
    if (length > 50) {
      return score + 2;
    }
    return length > 20 ? score + 1 : score;
  }, 0);
}
