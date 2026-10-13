import { InternationalExamCatalogSchema, type InternationalExamCatalog } from "@daydreaming/shared";

const catalog: InternationalExamCatalog = {
  countries: [
    {
      id: "usa",
      name: "Estados Unidos",
      flag: "🇺🇸",
      continent: "america",
      requirements: [
        "Graduação: SAT ou ACT + TOEFL/IELTS",
        "Pós-graduação: GRE ou GMAT + TOEFL/IELTS",
        "SAT Subject Tests: Para universidades competitivas",
        "LSAT: Para Direito",
        "MCAT: Para Medicina",
      ],
    },
    {
      id: "canada",
      name: "Canadá",
      flag: "🇨🇦",
      continent: "america",
      requirements: [
        "Inglês: IELTS ou TOEFL",
        "Francês: DELF/DALF (Quebec)",
        "Graduação: Varia por província",
        "Pós-graduação: GRE/GMAT conforme área",
        "TEF: Test d'Évaluation de Français",
      ],
    },
    {
      id: "argentina",
      name: "Argentina",
      flag: "🇦🇷",
      continent: "america",
      requirements: [
        "Espanhol: DELE ou SIELE",
        "Inglês: TOEFL/IELTS (cursos em inglês)",
        "Graduação: Bachillerato ou equivalente",
        "Universidades públicas: Gratuitas",
      ],
    },
    {
      id: "uk",
      name: "Reino Unido",
      flag: "🇬🇧",
      continent: "europa",
      requirements: [
        "Proficiência: IELTS (preferencial) ou TOEFL",
        "Graduação: A-Levels ou IB",
        "UKCAT/BMAT: Para Medicina",
        "LNAT: Para Direito",
        "IELTS for UKVI: Para vistos",
      ],
    },
    {
      id: "australia",
      name: "Austrália",
      flag: "🇦🇺",
      continent: "oceania",
      requirements: [
        "Proficiência: IELTS (preferencial) ou TOEFL",
        "Graduação: ATAR ou IB",
        "UCAT: Para Medicina/Odontologia",
        "GAMSAT: Graduate Medical School",
        "PTE Academic: Alternativa ao IELTS",
      ],
    },
    {
      id: "germany",
      name: "Alemanha",
      flag: "🇩🇪",
      continent: "europa",
      requirements: [
        "Alemão: TestDaF ou DSH",
        "Inglês: TOEFL/IELTS (cursos em inglês)",
        "Graduação: Abitur ou Studienkolleg",
        "Goethe-Zertifikat: Certificação alemã",
      ],
    },
    {
      id: "france",
      name: "França",
      flag: "🇫🇷",
      continent: "europa",
      requirements: [
        "Francês: DELF/DALF ou TCF",
        "Inglês: TOEFL/IELTS (cursos em inglês)",
        "Graduação: Baccalauréat",
        "Grandes Écoles: Concursos específicos",
      ],
    },
    {
      id: "spain",
      name: "Espanha",
      flag: "🇪🇸",
      continent: "europa",
      requirements: [
        "Espanhol: DELE ou SIELE",
        "Inglês: TOEFL/IELTS (cursos em inglês)",
        "Graduação: Bachillerato + PCE",
        "EBAU: Evaluación de Bachillerato",
      ],
    },
    {
      id: "italy",
      name: "Itália",
      flag: "🇮🇹",
      continent: "europa",
      requirements: [
        "Italiano: CILS ou CELI",
        "Inglês: TOEFL/IELTS (cursos em inglês)",
        "Graduação: Diploma di Maturità",
        "TOLC: Test Online CISIA",
      ],
    },
    {
      id: "japan",
      name: "Japão",
      flag: "🇯🇵",
      continent: "asia",
      requirements: [
        "Japonês: JLPT (N1-N5)",
        "Inglês: TOEFL/IELTS",
        "EJU: Examination for Japanese Universities",
        "Graduação: 12 anos de educação",
      ],
    },
    {
      id: "china",
      name: "China",
      flag: "🇨🇳",
      continent: "asia",
      requirements: [
        "Chinês: HSK (1-6) ou HSKK",
        "Inglês: TOEFL/IELTS (cursos em inglês)",
        "Graduação: Gaokao ou equivalente",
        "BCT: Business Chinese Test",
      ],
    },
    {
      id: "southkorea",
      name: "Coreia do Sul",
      flag: "🇰🇷",
      continent: "asia",
      requirements: [
        "Coreano: TOPIK (1-6)",
        "Inglês: TOEFL/IELTS",
        "KSAT: Korean Scholastic Aptitude Test",
        "Graduação: 12 anos de educação",
      ],
    },
    {
      id: "newzealand",
      name: "Nova Zelândia",
      flag: "🇳🇿",
      continent: "oceania",
      requirements: [
        "Proficiência: IELTS ou TOEFL",
        "Graduação: NCEA Level 3 ou IB",
        "UCAT ANZ: Para Medicina",
        "Imigração: IELTS General Training",
      ],
    },
    {
      id: "southafrica",
      name: "África do Sul",
      flag: "🇿🇦",
      continent: "africa",
      requirements: [
        "Inglês: IELTS ou TOEFL",
        "Graduação: National Senior Certificate",
        "NBT: National Benchmark Tests",
        "Afrikaans: Para algumas universidades",
      ],
    },
  ],
  exams: [
    {
      id: "toefl",
      name: "TOEFL",
      subtitle: "Test of English as a Foreign Language",
      audience:
        "Estudantes que desejam fazer graduação ou pós-graduação em universidades de países de língua inglesa, principalmente EUA e Canadá.",
      format:
        "Exame 100% online com foco em ambiente acadêmico. Avalia leitura, audição, escrita e fala. Duração: 3h30. Pontuação: 0 a 120 pontos.",
      noteLabel: "Custo",
      note: "Aproximadamente R$ 1.300 (versão iBT) ou R$ 690-750 (versão ITP).",
    },
    {
      id: "ielts",
      name: "IELTS",
      subtitle: "International English Language Testing System",
      audience:
        "Estudantes e profissionais que querem estudar ou trabalhar no Reino Unido, Austrália, Irlanda, Nova Zelândia e mais de 140 países.",
      format:
        "Pode ser feito no papel ou computador. Avalia as quatro habilidades do idioma. Duração: 2h45. Pontuação: 0 a 9.",
      noteLabel: "Custo",
      note: "Entre R$ 1.171 a R$ 1.265, dependendo da modalidade.",
    },
    {
      id: "sat",
      name: "SAT",
      subtitle: "Scholastic Assessment Test",
      audience:
        "Estudantes que desejam ingressar em universidades americanas para graduação. Equivalente ao ENEM brasileiro.",
      format:
        "Duas seções principais: Matemática (800 pontos) e Leitura/Escrita (800 pontos). Pontuação máxima: 1600. Duração: 3 horas.",
      noteLabel: "Custo",
      note: "US$ 51 + taxa regional de US$ 31 para o Brasil.",
    },
    {
      id: "act",
      name: "ACT",
      subtitle: "American College Test",
      audience:
        "Alternativa ao SAT para ingresso em universidades americanas. Aceito pela maioria das instituições dos EUA.",
      format:
        "Quatro seções: Inglês, Matemática, Interpretação de texto e Raciocínio científico. Pontuação: 1 a 36. Duração: 2h45.",
      noteLabel: "Diferencial",
      note: "Inclui seção de ciências, ideal para quem tem bom desempenho em ciências da natureza.",
    },
    {
      id: "gre",
      name: "GRE",
      subtitle: "Graduate Record Examination",
      audience:
        "Estudantes que desejam fazer mestrado ou doutorado em diversas áreas (exceto MBA e medicina).",
      format:
        "Avalia raciocínio verbal, quantitativo e escrita analítica. Teste baseado em computador.",
      noteLabel: "Uso",
      note: "Amplamente aceito para programas de pós-graduação em universidades americanas e internacionais.",
    },
    {
      id: "gmat",
      name: "GMAT",
      subtitle: "Graduate Management Admission Test",
      audience: "Candidatos a programas de MBA e pós-graduação em administração e negócios.",
      format:
        "Quatro seções: Escrita analítica, Raciocínio integrado, Quantitativo e Verbal. Teste online.",
      noteLabel: "Foco",
      note: "Habilidades específicas para gestão e administração de empresas.",
    },
  ],
  regionalGuides: [
    {
      id: "usa",
      country: "Estados Unidos",
      requirements: [
        "Graduação: SAT ou ACT + TOEFL/IELTS",
        "Pós-graduação: GRE ou GMAT + TOEFL/IELTS",
        "SAT Subject Tests: Para universidades mais competitivas",
        "Universidades aceitas: Todas as principais universidades",
      ],
    },
    {
      id: "uk",
      country: "Reino Unido",
      requirements: [
        "Proficiência: IELTS (preferencial) ou TOEFL",
        "Graduação: A-Levels ou equivalente internacional",
        "Pós-graduação: Varia por universidade",
        "Especial: IELTS for UKVI para vistos",
      ],
    },
    {
      id: "canada",
      country: "Canadá",
      requirements: [
        "Inglês: IELTS ou TOEFL",
        "Francês: DELF/DALF (Quebec)",
        "Graduação: Varia por província",
        "Pós-graduação: GRE/GMAT conforme área",
      ],
    },
    {
      id: "australia",
      country: "Austrália",
      requirements: [
        "Proficiência: IELTS (preferencial)",
        "Graduação: ATAR ou equivalente internacional",
        "Pós-graduação: Varia por universidade",
        "Imigração: IELTS General Training",
      ],
    },
    {
      id: "france",
      country: "França",
      requirements: [
        "Francês: DELF/DALF",
        "Inglês: TOEFL/IELTS (cursos em inglês)",
        "Graduação: Baccalauréat ou equivalente",
        "Grandes Écoles: Concursos específicos",
      ],
    },
    {
      id: "germany",
      country: "Alemanha",
      requirements: [
        "Alemão: TestDaF ou DSH",
        "Inglês: TOEFL/IELTS (cursos em inglês)",
        "Graduação: Abitur ou equivalente",
        "Universidades públicas: Geralmente gratuitas",
      ],
    },
  ],
  tips: [
    {
      title: "Planeje com antecedência",
      description: "Alguns testes têm poucas datas disponíveis e podem ter lista de espera.",
    },
    {
      title: "Conheça o formato",
      description: "Cada teste tem estrutura específica. Pratique com simulados oficiais.",
    },
    {
      title: "Verifique a validade",
      description: "TOEFL e IELTS são válidos por 2 anos. Cambridge não expira.",
    },
    {
      title: "Escolha estrategicamente",
      description: "SAT vs ACT, TOEFL vs IELTS - considere suas habilidades.",
    },
    {
      title: "Prepare-se adequadamente",
      description: "Invista em cursos preparatórios ou materiais de qualidade.",
    },
    {
      title: "Considere múltiplas tentativas",
      description: "A maioria dos testes pode ser refeita para melhorar a pontuação.",
    },
    {
      title: "Verifique requisitos específicos",
      description: "Cada universidade tem exigências diferentes de pontuação mínima.",
    },
    {
      title: "Documente tudo",
      description: "Mantenha cópias de todos os certificados e resultados.",
    },
  ],
};

export function getInternationalExamCatalog(): InternationalExamCatalog {
  return InternationalExamCatalogSchema.parse(catalog);
}
