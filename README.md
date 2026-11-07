# Laravel Agentic Orchestrator

Enterprise-grade autonomous AI Agent orchestration engine built on Laravel and PHP, implementing recursive ReAct decision loops, strict JSON-schema tool calling, and vector RAG retrieval.

---

## Architecture Flowchart

```mermaid
flowchart TD
    User([User Prompt / Enterprise Request]) --> CLI[Artisan CLI / REST API Gateway]
    CLI --> Orch[AgentOrchestrator]
    
    subgraph ReAct_Autonomous_Loop ["Autonomous ReAct Loop"]
        Orch --> PromptCtx[Compile Prompt & Context History]
        PromptCtx --> LLM[LLM Engine / Function Calling API]
        
        Decision{Requires Tool Call?}
        LLM --> Decision
        
        Decision -- Yes --> ParseTool[Extract Tool & JSON Arguments]
        ParseTool --> Validate[Validate Parameters against ToolInterface]
        Validate --> RagTool[Execute DocumentSearchRagTool]
        
        subgraph Vector_RAG ["Knowledge Retrieval Layer"]
            RagTool --> VectorDB[(Vector Store / Enterprise Playbooks)]
            VectorDB --> Chunks[Retrieve Top-K Architecture Chunks]
        end
        
        Chunks --> Ingest[Append Observation to Message History]
        Ingest --> PromptCtx
    end
    
    Decision -- No --> FinalAnswer[Synthesize Final Architectural Decision]
    FinalAnswer --> Output([Verified Solutions Output to User])
